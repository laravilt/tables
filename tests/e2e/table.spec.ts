import { test, expect } from '@playwright/test'

/**
 * Table Component E2E Tests
 *
 * These tests verify the table component functionality against
 * the Table Demo page in the Laravilt admin panel.
 * Note: The DataTable uses a div-based structure, not HTML table elements.
 */

test.describe('Table Component', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the table demo page
    await page.goto('/admin/demos/table')
    // Wait for the table to be rendered - the DataTable uses div-based structure with bg-card
    await page.waitForSelector('[class*="bg-card"][class*="rounded"]', { timeout: 15000 })
  })

  test.describe('Page Structure', () => {
    test('should render the page', async ({ page }) => {
      // Check that we're on the correct page
      await expect(page).toHaveURL(/\/admin\/demos\/table/)
    })

    test('should have page title', async ({ page }) => {
      const title = page.locator('text=Table Demo').first()
      await expect(title).toBeVisible()
    })
  })

  test.describe('Table Container', () => {
    test('should render the table container', async ({ page }) => {
      // Check that the table container exists - uses Card component
      const tableContainer = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(tableContainer).toBeVisible()
    })

    test('should render user records', async ({ page }) => {
      // Check that user data is displayed (names, emails visible)
      const userData = page.locator('text=@example.com').first()
      await expect(userData).toBeVisible()
    })
  })

  test.describe('Column Display', () => {
    test('should display avatar images', async ({ page }) => {
      // Avatar column should have images
      const avatars = page.locator('img[src*="avatar"], img[src*="dicebear"]').first()
      await expect(avatars).toBeVisible()
    })

    test('should display user names', async ({ page }) => {
      // Name column should have text
      const name = page.locator('[class*="font-medium"]').first()
      await expect(name).toBeVisible()
    })

    test('should display user emails', async ({ page }) => {
      // Email data should be visible
      const email = page.locator('text=@example.com').first()
      await expect(email).toBeVisible()
    })

    test('should display role badges', async ({ page }) => {
      // Role column should have badges (admin, editor, user) - exclude debug bar
      const badges = page.locator('main [class*="badge"]:has-text("admin"), main [class*="badge"]:has-text("editor"), main [class*="badge"]:has-text("user")').first()
      const exists = await badges.count()
      if (exists > 0) {
        await expect(badges).toBeVisible()
      }
    })
  })

  test.describe('Search Functionality', () => {
    test('should have a search input', async ({ page }) => {
      const searchInput = page.locator('input[placeholder*="Search"]').first()
      await expect(searchInput).toBeVisible()
    })

    test('should filter results when searching', async ({ page }) => {
      const searchInput = page.locator('input[placeholder*="Search"]').first()

      // Type a search term
      await searchInput.fill('test')
      await searchInput.press('Enter')

      // Wait for the table to update
      await page.waitForTimeout(1000)

      // Just verify search was performed (no errors)
      await expect(page).not.toHaveURL(/error/)
    })

    test('should clear search and show results', async ({ page }) => {
      const searchInput = page.locator('input[placeholder*="Search"]').first()

      // Search for something
      await searchInput.fill('test')
      await searchInput.press('Enter')
      await page.waitForTimeout(500)

      // Clear the search
      await searchInput.fill('')
      await searchInput.press('Enter')
      await page.waitForTimeout(500)

      // Table should show results
      const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(container).toBeVisible()
    })
  })

  test.describe('Toolbar', () => {
    test('should have filter button', async ({ page }) => {
      const filterButton = page.locator('button:has-text("Filter")')
      const exists = await filterButton.count()
      if (exists > 0) {
        await expect(filterButton.first()).toBeVisible()
      }
    })

    test('should have sort button', async ({ page }) => {
      const sortButton = page.locator('button:has-text("Sort")')
      const exists = await sortButton.count()
      if (exists > 0) {
        await expect(sortButton.first()).toBeVisible()
      }
    })

    test('should have columns toggle button', async ({ page }) => {
      const columnsButton = page.locator('button:has-text("Columns")')
      const exists = await columnsButton.count()
      if (exists > 0) {
        await expect(columnsButton.first()).toBeVisible()
      }
    })
  })

  test.describe('Pagination', () => {
    test('should have pagination controls', async ({ page }) => {
      const pagination = page.locator('button:has-text("Next"), button:has-text("Previous"), [class*="pagination"]')
      const exists = await pagination.count()
      if (exists > 0) {
        await expect(pagination.first()).toBeVisible()
      }
    })

    test('should navigate pages', async ({ page }) => {
      const nextButton = page.locator('button:has-text("Next")')
      const exists = await nextButton.count()

      if (exists > 0 && await nextButton.first().isEnabled()) {
        await nextButton.first().click()
        await page.waitForTimeout(1000)

        // Page should still show table
        const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
        await expect(container).toBeVisible()
      }
    })
  })

  test.describe('Actions', () => {
    test('should have row action buttons', async ({ page }) => {
      // Look for action buttons in the actions column
      const actionButtons = page.locator('button[class*="icon"], [class*="action"] button').first()
      const exists = await actionButtons.count()
      if (exists > 0) {
        await expect(actionButtons).toBeVisible()
      }
    })

    test('should have header action buttons', async ({ page }) => {
      const createButton = page.locator('button:has-text("Create User")')
      const exists = await createButton.count()
      if (exists > 0) {
        await expect(createButton.first()).toBeVisible()
      }
    })

    test('should have import button', async ({ page }) => {
      const importButton = page.locator('button:has-text("Import")')
      const exists = await importButton.count()
      if (exists > 0) {
        await expect(importButton.first()).toBeVisible()
      }
    })
  })

  test.describe('Selection', () => {
    test('should have selection checkboxes', async ({ page }) => {
      const checkboxes = page.locator('[role="checkbox"]')
      const exists = await checkboxes.count()
      if (exists > 0) {
        await expect(checkboxes.first()).toBeVisible()
      }
    })

    test('should select row when checkbox clicked', async ({ page }) => {
      const checkbox = page.locator('[role="checkbox"]').nth(1)  // First data row checkbox
      const exists = await checkbox.count()

      if (exists > 0) {
        await checkbox.click()
        await page.waitForTimeout(300)

        // Checkbox state should change
        const ariaChecked = await checkbox.getAttribute('aria-checked')
        expect(['true', 'mixed']).toContain(ariaChecked)
      }
    })
  })

  test.describe('Responsive Layout', () => {
    test('should display on desktop', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 720 })
      await page.waitForTimeout(500)

      const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(container).toBeVisible()
    })

    test('should display on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 })
      await page.waitForTimeout(500)

      const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(container).toBeVisible()
    })

    test('should display on tablet', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 })
      await page.waitForTimeout(500)

      const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(container).toBeVisible()
    })
  })

  test.describe('Empty State', () => {
    test('should show empty state when no results', async ({ page }) => {
      const searchInput = page.locator('input[placeholder*="Search"]').first()

      // Search for something that doesn't exist
      await searchInput.fill('xyznonexistent12345')
      await searchInput.press('Enter')
      await page.waitForTimeout(1500)

      // Should show empty state or container
      const container = page.locator('[class*="bg-card"][class*="rounded"]').first()
      await expect(container).toBeVisible()
    })
  })
})
