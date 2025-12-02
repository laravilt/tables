<?php

namespace Laravilt\Tables\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchDocsTool extends Tool
{
    protected string $description = 'Search the Laravilt Tables documentation';

    public function handle(Request $request): Response
    {
        $query = $request->string('query');
        $packagePath = base_path('packages/laravilt/tables');

        $docFiles = $this->getDocumentationFiles($packagePath);
        $results = $this->searchDocumentation($docFiles, $query);

        if (empty($results)) {
            return Response::text("No documentation found matching '{$query}'.");
        }

        $output = "Documentation Search Results for: {$query}\n\n";
        $output .= 'Found '.count($results)." relevant section(s):\n\n";

        foreach ($results as $result) {
            $output .= "📄 {$result['file']}\n";
            $output .= str_repeat('=', 60)."\n\n";
            $output .= $result['content']."\n\n";
            $output .= str_repeat('-', 60)."\n\n";
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('QUERY_Search the Laravilt Tables documentation')
                ->required(),
        ];
    }

    protected function getDocumentationFiles(string $path): array
    {
        $files = [];

        if (File::exists($path.'/README.md')) {
            $files[] = [
                'path' => $path.'/README.md',
                'name' => 'README.md',
                'content' => File::get($path.'/README.md'),
            ];
        }

        $docsPath = $path.'/docs';
        if (File::isDirectory($docsPath)) {
            foreach (File::allFiles($docsPath) as $file) {
                if ($file->getExtension() === 'md') {
                    $relativePath = str_replace($path.'/', '', $file->getPathname());
                    $files[] = [
                        'path' => $file->getPathname(),
                        'name' => $relativePath,
                        'content' => File::get($file->getPathname()),
                    ];
                }
            }
        }

        return $files;
    }

    protected function searchDocumentation(array $files, string $query): array
    {
        $results = [];
        $queryLower = strtolower($query);
        $keywords = explode(' ', $queryLower);

        foreach ($files as $file) {
            $content = $file['content'];
            $contentLower = strtolower($content);

            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (stripos($contentLower, $keyword) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $sections = $this->extractRelevantSections($content, $query, $keywords);
                foreach ($sections as $section) {
                    $results[] = [
                        'file' => $file['name'],
                        'content' => $section,
                        'relevance' => $matchCount,
                    ];
                }
            }
        }

        usort($results, fn ($a, $b) => $b['relevance'] <=> $a['relevance']);
        return array_slice($results, 0, 5);
    }

    protected function extractRelevantSections(string $content, string $query, array $keywords): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = '';
        $currentHeader = '';
        $inRelevantSection = false;

        foreach ($lines as $line) {
            if (preg_match('/^#+\s+(.+)$/', $line, $matches)) {
                if ($inRelevantSection && !empty(trim($currentSection))) {
                    $sections[] = trim($currentHeader."\n\n".$currentSection);
                }

                $currentHeader = $line;
                $currentSection = '';
                $headerLower = strtolower($matches[1]);
                $inRelevantSection = false;
                foreach ($keywords as $keyword) {
                    if (stripos($headerLower, $keyword) !== false) {
                        $inRelevantSection = true;
                        break;
                    }
                }
            } else {
                $currentSection .= $line."\n";
                if (!$inRelevantSection) {
                    $lineLower = strtolower($line);
                    foreach ($keywords as $keyword) {
                        if (stripos($lineLower, $keyword) !== false) {
                            $inRelevantSection = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($inRelevantSection && !empty(trim($currentSection))) {
            $sections[] = trim($currentHeader."\n\n".$currentSection);
        }

        return $sections;
    }
}
