<?php

use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Support\HtmlSanitizer;
use Laravilt\Tables\Table;

it('keeps safe markup intact', function () {
    $html = '<p>Hello <strong>world</strong> <a href="https://example.com" title="x">link</a></p>';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
});

it('preserves utf-8 text', function () {
    expect(HtmlSanitizer::sanitize('<em>مرحبا café</em>'))->toBe('<em>مرحبا café</em>');
});

it('returns empty and null input unchanged', function () {
    expect(HtmlSanitizer::sanitize(''))->toBe('')
        ->and(HtmlSanitizer::sanitize(null))->toBe('');
});

it('strips dangerous elements with their content', function (string $tag) {
    $output = HtmlSanitizer::sanitize("<p>ok</p><{$tag}>alert(1)</{$tag}>");

    expect($output)->toBe('<p>ok</p>')
        ->and(strtolower($output))->not->toContain($tag);
})->with(['script', 'style', 'iframe', 'object']);

it('strips embed, which is a void element without content', function () {
    $output = HtmlSanitizer::sanitize('<p>ok</p><embed src="x.swf">alert(1)</embed>');

    // <embed> cannot have children: HTML5 parsers (browsers, libxml >= 2.14) keep what follows it
    // as plain, inert text, while older libxml versions parsed it as content of the element
    expect($output)->toBeIn(['<p>ok</p>alert(1)', '<p>ok</p>'])
        ->and(strtolower($output))->not->toContain('embed')
        ->and($output)->not->toContain('x.swf');
});

it('strips self-closing embed and nested scripts', function () {
    $output = HtmlSanitizer::sanitize('<div><span><script>alert(1)</script>text</span><embed src="x.swf"></div>');

    expect($output)->toBe('<div><span>text</span></div>');
});

it('strips svg animation elements that can retarget href to javascript urls', function (string $html) {
    $output = strtolower(HtmlSanitizer::sanitize($html));

    expect($output)->not->toContain('javascript:')
        ->and($output)->not->toContain('<animate')
        ->and($output)->not->toContain('<set')
        ->and($output)->toContain('<a');
})->with([
    'animate values' => ['<svg><a><animate attributeName="href" values="javascript:alert(1)"/><text x="20" y="20">click</text></a></svg>'],
    'animate to' => ['<svg><a><animate attributeName="href" to="javascript:alert(1)"/><text>click</text></a></svg>'],
    'set to' => ['<svg><a><set attributeName="href" to="javascript:alert(1)"/><text>click</text></a></svg>'],
    'set xlink:href' => ['<svg><a xlink:href="#"><set attributeName="xlink:href" to="javascript:alert(1)"/><text>click</text></a></svg>'],
]);

it('strips all four svg animation elements', function (string $tag) {
    $output = HtmlSanitizer::sanitize("<svg><circle r=\"5\"><{$tag} attributeName=\"r\" to=\"10\"></{$tag}></circle></svg>");

    expect(strtolower($output))->not->toContain(strtolower($tag))
        ->and($output)->toContain('<circle');
})->with(['animate', 'set', 'animateMotion', 'animateTransform']);

it('strips event handler attributes', function () {
    $output = HtmlSanitizer::sanitize('<img src="a.png" onerror="alert(1)" ONLOAD="x()"><b onclick="y()">b</b>');

    expect($output)->not->toContain('onerror')
        ->and(strtolower($output))->not->toContain('onload')
        ->and($output)->not->toContain('onclick')
        ->and($output)->toContain('src="a.png"')
        ->and($output)->toContain('<b>b</b>');
});

it('strips javascript and data urls', function (string $html) {
    $output = strtolower(HtmlSanitizer::sanitize($html));

    expect($output)->not->toContain('javascript')
        ->and($output)->not->toContain('data:')
        ->and($output)->not->toContain('vbscript');
})->with([
    '<a href="javascript:alert(1)">x</a>',
    '<a href="  JaVaScRiPt:alert(1)">x</a>',
    "<a href=\"java\tscript:alert(1)\">x</a>",
    '<a href="&#106;avascript:alert(1)">x</a>',
    '<img src="data:image/svg+xml;base64,PHN2Zz4=">',
    '<a href="vbscript:msgbox(1)">x</a>',
    '<form action="javascript:alert(1)"><button formaction="javascript:alert(1)">x</button></form>',
    '<svg><a xlink:href="javascript:alert(1)">x</a></svg>',
]);

it('allows base64 raster data urls in img src', function (string $mime) {
    $html = '<img src="data:image/'.$mime.';base64,iVBORw0KGgoAAAANSUhEUg==" alt="x">';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
})->with(['png', 'jpeg', 'jpg', 'gif', 'webp', 'avif', 'PNG']);

it('blocks data urls that are not base64 raster images in img src', function (string $html) {
    expect(strtolower(HtmlSanitizer::sanitize($html)))->not->toContain('data:');
})->with([
    'svg' => '<img src="data:image/svg+xml;base64,PHN2Zz4=">',
    'svg plain' => '<img src="data:image/svg+xml,<svg onload=alert(1)>">',
    'text/html' => '<img src="data:text/html;base64,PHNjcmlwdD4=">',
    'non-base64 png' => '<img src="data:image/png,abc">',
    'trailing payload' => '<img src="data:image/png;base64,AAAA,javascript:alert(1)">',
    'img srcset' => '<img srcset="data:image/png;base64,AAAA 1x">',
    'a href' => '<a href="data:image/png;base64,AAAA">x</a>',
    'a href html' => '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
    'source src' => '<video><source src="data:image/png;base64,AAAA"></video>',
    'iframe-like object data' => '<div data="data:image/png;base64,AAAA">x</div>',
]);

it('keeps relative and mailto urls', function () {
    $html = '<a href="/admin/users/1">u</a><a href="mailto:a@b.c">m</a>';

    expect(HtmlSanitizer::sanitize($html))->toBe($html);
});

it('strips dangerous inline styles but keeps safe ones', function () {
    expect(HtmlSanitizer::sanitize('<span style="color: red">x</span>'))->toBe('<span style="color: red">x</span>')
        ->and(HtmlSanitizer::sanitize('<span style="background:url(javascript:alert(1))">x</span>'))->toBe('<span>x</span>');
});

it('strips html comments', function () {
    expect(HtmlSanitizer::sanitize('<p>a<!-- <script>x</script> --></p>'))->toBe('<p>a</p>');
});

it('exposes the html flag on text columns', function () {
    expect(TextColumn::make('bio')->isHtml())->toBeFalse()
        ->and(TextColumn::make('bio')->html()->isHtml())->toBeTrue();
});

it('sanitizes html text column values when processing records', function () {
    $table = Table::make()->columns([
        TextColumn::make('bio')->html(),
        TextColumn::make('name'),
    ]);

    $method = new ReflectionMethod($table, 'processRecords');
    $records = $method->invoke($table, [[
        'id' => 1,
        'bio' => '<p onclick="x()">Hi</p><script>alert(1)</script>',
        'name' => '<b>plain</b>',
    ]]);

    // HTML column is sanitized; non-HTML columns are left alone (the frontend escapes them)
    expect($records[0]['bio'])->toBe('<p>Hi</p>')
        ->and($records[0]['name'])->toBe('<b>plain</b>');
});

it('sanitizes html produced by formatStateUsing', function () {
    $table = Table::make()->columns([
        TextColumn::make('bio')->html()->formatStateUsing(fn ($state) => $state.'<img src=x onerror=alert(1)>'),
    ]);

    $method = new ReflectionMethod($table, 'processRecords');
    $records = $method->invoke($table, [['id' => 1, 'bio' => '<i>Hi</i>']]);

    expect($records[0]['bio'])->toBe('<i>Hi</i><img src="x">');
});
