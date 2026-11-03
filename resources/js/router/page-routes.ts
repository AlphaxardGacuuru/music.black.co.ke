export type RegisteredPageRoute = {
	componentName: string
	path: string
}

const routeOverrides: Record<string, string> = {
	"auth/verify-email": "/email/verify",
	"auth/reset-password": "/reset-password/$token",
}

export function componentNameToPath(componentName: string): string {
	if (componentName === "home") {
		return "/"
	}

	if (componentName in routeOverrides) {
		return routeOverrides[componentName]
	}

	const withoutAuthPrefix = componentName.startsWith("auth/")
		? componentName.slice(5)
		: componentName
	const normalized = withoutAuthPrefix
		.replace(/\/index$/, "")
		.replace(/\[([^\]]+)\]/g, (_match, param: string) => `$${param}`)

	return normalized.length > 0 ? `/${normalized}` : "/"
}

function modulePathToComponentName(
	modulePath: string,
	pagesPrefix: string
): string {
	return modulePath.replace(pagesPrefix, "").replace(/\.tsx$/, "")
}

export function discoverPageRoutes(
	modulePaths: string[],
	pagesPrefix: string
): RegisteredPageRoute[] {
	return modulePaths
		.map((modulePath) => modulePathToComponentName(modulePath, pagesPrefix))
		.map((componentName): RegisteredPageRoute => {
			return {
				componentName,
				path: componentNameToPath(componentName),
			}
		})
		.reduce<RegisteredPageRoute[]>((uniqueRoutes, route) => {
			if (!uniqueRoutes.some((item) => item.path === route.path)) {
				uniqueRoutes.push(route)
			}

			return uniqueRoutes
		}, [])
}

/**
 * Whether a concrete pathname (e.g. "/chat/@fan/view") belongs to one of the
 * page routes, including dynamic ones like "/chat/$username/view", so links
 * to it can navigate client side instead of reloading the page.
 */
export function buildClientRouteMatcher(
	modulePaths: string[],
	pagesPrefix: string
): (pathname: string) => boolean {
	const patterns = discoverPageRoutes(modulePaths, pagesPrefix).map(
		(route) =>
			new RegExp(
				`^${route.path
					.split("/")
					.map((segment) =>
						segment.startsWith("$")
							? "[^/]+"
							: segment.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
					)
					.join("/")}/?$`
			)
	)

	return (pathname) => patterns.some((pattern) => pattern.test(pathname))
}
