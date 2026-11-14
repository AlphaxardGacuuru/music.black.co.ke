import { execSync } from "node:child_process"
import { expect, test as setup } from "@playwright/test"

export const E2E_EMAIL = process.env.E2E_EMAIL ?? "e2e@example.test"
export const E2E_PASSWORD = process.env.E2E_PASSWORD ?? "e2e-smoke-password"

// Locally artisan runs through Sail; CI sets E2E_ARTISAN="php artisan".
const ARTISAN = process.env.E2E_ARTISAN ?? "./vendor/bin/sail artisan"

setup("signs in the smoke test user", async ({ page }) => {
	// Creates the user, or resets its password, so a fresh database works too.
	// The model fills in the "@handle" itself when the user is created.
	execSync(
		`${ARTISAN} tinker --execute '${[
			"App\\Models\\User::query()",
			`->updateOrCreate(["email" => "${E2E_EMAIL}"], [`,
			'"name" => "E2E Smoke Test",',
			'"email_verified_at" => now(),',
			`"password" => Illuminate\\Support\\Facades\\Hash::make("${E2E_PASSWORD}"),`,
			"]);",
		].join(" ")}'`,
		{ stdio: "inherit" }
	)

	await page.goto("/login")
	await page
		.locator('input[name="email"]')
		.fill(E2E_EMAIL)
	await page
		.locator('input[name="password"]')
		.fill(E2E_PASSWORD)
	await page
		.getByRole("button", { name: /log in/i })
		.click()

	await expect(page).not.toHaveURL(/\/login/, { timeout: 15_000 })
	// The app signs in with a Sanctum token kept in localStorage (not a
	// session cookie), and storageState saves localStorage along with cookies.
	await page.waitForFunction(() => localStorage.getItem("sanctumToken"))

	await page
		.context()
		.storageState({ path: "tests/e2e/.auth/user.json" })
})
