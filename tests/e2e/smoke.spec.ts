import { existsSync } from "node:fs"
import { expect, test } from "@playwright/test"
import type { Page } from "@playwright/test"

/** Pages that need a signed in user. */
const PAGES = [
	"/",
	"/videos/charts",
	"/audios/charts",
	"/karaoke/charts",
	"/search",
	"/cart",
	"/library",
	"/notifications",
	"/chat",
	"/videos",
	"/audios",
	"/settings/profile",
	"/settings/security",
	"/settings/appearance",
	"/settings/payouts",
]

/** Pages guests can open without signing in. */
const PUBLIC_PAGES = [
	"/welcome",
	"/privacy",
	"/search",
	"/videos/charts",
	"/audios/charts",
	"/karaoke/charts",
	"/login",
	"/register",
]

// Known app bugs that log console errors. Each listed page's "logs no
// errors" test is marked fixme until the bug is fixed, so drop the entry then.
const USER_CHANNEL_BUG =
	"useNotifications (resources/js/hooks/use-notifications.ts) subscribes to private-App.Models.User.0 before auth has loaded (or for guests), so /api/broadcasting/auth answers 403 (401 for guests)"
const SEEDED_MEDIA_BUG =
	"the seeded posts, videos, audios and karaokes point at media the factories never create (e.g. /storage/video-thumbnails/1.jpg), so they answer 403"
const GUEST_AUTH_BUG =
	"AppContext (resources/js/contexts/AppContext.tsx) fetches /api/auth even with no token, so guests log 401s (retried 3 times)"

const SIGNED_IN_BUGS: Record<string, string> = {
	"/": SEEDED_MEDIA_BUG,
	"/videos/charts": `${SEEDED_MEDIA_BUG}; ${USER_CHANNEL_BUG}`,
	"/audios/charts": `${SEEDED_MEDIA_BUG}; ${USER_CHANNEL_BUG}`,
	"/karaoke/charts": `${SEEDED_MEDIA_BUG}; ${USER_CHANNEL_BUG}`,
	"/search": `${SEEDED_MEDIA_BUG}; ${USER_CHANNEL_BUG}`,
}

const GUEST_BUGS: Record<string, string> = Object.fromEntries(
	PUBLIC_PAGES.map((path) => [path, `${GUEST_AUTH_BUG}; ${USER_CHANNEL_BUG}`])
)

GUEST_BUGS["/privacy"] += "; the embedded iubenda policy (https://www.iubenda.com/privacy-policy/38639633) answers 404"

/** Collects uncaught errors and console errors while a page is open. */
function watchErrors(page: Page): string[] {
	const errors: string[] = []

	page.on("pageerror", (error) => errors.push(error.message))
	page.on("console", (message) => {
		if (message.type() === "error") {
			errors.push(message.text())
		}
	})

	return errors
}

/** The app mounted something, so the screen isn't blank. */
async function expectAppRendered(page: Page) {
	await expect(page.locator("#app > *").first()).toBeVisible({ timeout: 15_000 })
}

/** Opens a page and waits for it to settle, returning the errors it logged. */
async function openPage(page: Page, path: string): Promise<string[]> {
	const errors = watchErrors(page)

	await page.goto(path)
	await expectAppRendered(page)
	await expect(page).toHaveURL(new RegExp(`${path}$`))
	await page.waitForLoadState("networkidle")

	return errors
}

/** How many pixels the page sticks out past the right edge of the screen. */
function horizontalOverflow(page: Page): Promise<number> {
	return page.evaluate(
		() => document.documentElement.scrollWidth - window.innerWidth
	)
}

for (const path of PAGES) {
	test(`${path} opens and fits the phone screen`, async ({ page }) => {
		await openPage(page, path)
		await expect(page.getByRole("navigation").last()).toBeVisible()

		expect(await horizontalOverflow(page), "page is wider than the screen").toBeLessThanOrEqual(0)
	})

	test(`${path} logs no errors`, async ({ page }) => {
		test.fixme(path in SIGNED_IN_BUGS, SIGNED_IN_BUGS[path])

		expect(await openPage(page, path)).toEqual([])
	})
}

test.describe("signed out", () => {
	test.use({ storageState: { cookies: [], origins: [] } })

	for (const path of PUBLIC_PAGES) {
		test(`${path} opens for guests and fits the phone screen`, async ({ page }) => {
			await openPage(page, path)

			expect(await horizontalOverflow(page), "page is wider than the screen").toBeLessThanOrEqual(0)
		})

		test(`${path} logs no errors for guests`, async ({ page }) => {
			test.fixme(path in GUEST_BUGS, GUEST_BUGS[path])

			expect(await openPage(page, path)).toEqual([])
		})
	}

	test("guests opening the feed land on the welcome page", async ({ page }) => {
		await page.goto("/")
		await expect(page).toHaveURL(/\/welcome$/)
	})

	test("guests are sent to the login page from pages that need an account", async ({ page }) => {
		await page.goto("/library")
		await expect(page).toHaveURL(/\/login$/)
	})
})

test("the bottom nav moves between pages", async ({ page }) => {
	await page.goto("/")
	await expectAppRendered(page)

	const bottomNav = page.locator("nav.max-w-md")

	await bottomNav
		.getByRole("link", { name: "Library" })
		.click()
	await expect(page).toHaveURL(/\/library$/)

	await bottomNav
		.getByRole("link", { name: "Discover" })
		.click()
	await expect(page).toHaveURL(/\/videos\/charts$/)

	await bottomNav
		.getByRole("link", { name: "Cart" })
		.click()
	await expect(page).toHaveURL(/\/cart$/)

	await bottomNav
		.getByRole("link", { name: "Home" })
		.click()
	await expect(page).toHaveURL(/:\d+\/$/)
})

test("the app still opens offline once the service worker has it", async ({ page, context }) => {
	// The Vite dev server serves unbundled modules the service worker never
	// precaches, so this only means something against built assets.
	test.skip(existsSync("public/hot"), "needs built assets, not `npm run dev`")

	await page.goto("/library")
	await expectAppRendered(page)
	await page.evaluate(() => navigator.serviceWorker.ready)
	// Load once under the service worker's control so it caches the page.
	await page.reload()
	await expectAppRendered(page)

	await context.setOffline(true)
	await page.reload()
	await expectAppRendered(page)
	await context.setOffline(false)
})
