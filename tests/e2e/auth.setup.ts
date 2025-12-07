import { test as setup, expect } from '@playwright/test'

const authFile = 'tests/e2e/.auth/user.json'

/**
 * Authentication Setup
 *
 * Authenticates a test user and saves session state
 * Uses direct form submission to the login endpoint
 */
setup('authenticate', async ({ page, request }) => {
  // First, navigate to login page to get CSRF token and session
  await page.goto('/admin/login')

  // Wait for the login form to be visible
  await page.waitForSelector('text=Sign In', { timeout: 10000 })

  // Get the CSRF token from the page
  const csrfToken = await page.evaluate(() => {
    const meta = document.querySelector('meta[name="csrf-token"]')
    return meta?.getAttribute('content') || ''
  })

  console.log('CSRF Token:', csrfToken ? 'found' : 'not found')

  // Fill in the form
  await page.fill('input[name="email"]', 'test@example.com')
  await page.fill('input[name="password"]', 'password')

  // Wait a moment for Vue to be ready
  await page.waitForTimeout(500)

  // Click the "Sign In" button
  await page.click('button:has-text("Sign In")')

  // Wait for navigation - either success or showing error on same page
  try {
    // Wait for either successful redirect or error response
    await Promise.race([
      page.waitForURL(/\/admin(?!\/login)/, { timeout: 20000 }),
      page.waitForSelector('.text-red-500, [class*="error"], text=credentials', { timeout: 20000 })
    ])

    // Check if we're logged in
    const currentUrl = page.url()
    if (currentUrl.includes('/login')) {
      // Check for error message
      const errorText = await page.textContent('body')
      console.log('Still on login page. Checking for errors...')

      // If we see an error, the action system might have failed
      // Let's try a different approach
      throw new Error('Login action failed, trying alternative method')
    }

    await expect(page).not.toHaveURL(/\/login/)
    console.log('Login successful via action system')

  } catch (error) {
    console.log('Action-based login failed, attempting direct POST...')

    // Navigate back to login if needed
    if (!page.url().includes('/login')) {
      await page.goto('/admin/login')
      await page.waitForSelector('text=Sign In', { timeout: 10000 })
    }

    // Get fresh CSRF token
    const freshCsrfToken = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="csrf-token"]')
      return meta?.getAttribute('content') || ''
    })

    // Submit login directly via POST to admin/login (the store method)
    const response = await page.evaluate(async (token) => {
      const formData = new FormData()
      formData.append('email', 'test@example.com')
      formData.append('password', 'password')
      formData.append('_token', token)

      const res = await fetch('/admin/login', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'text/html, application/xhtml+xml',
        }
      })

      return { ok: res.ok, status: res.status, redirected: res.redirected, url: res.url }
    }, freshCsrfToken)

    console.log('Direct POST response:', response)

    // Reload to see the authenticated state
    await page.goto('/admin')
    await page.waitForTimeout(1000)
  }

  // Final check - we should be authenticated
  await expect(page).not.toHaveURL(/\/login/)

  await page.context().storageState({ path: authFile })
})
