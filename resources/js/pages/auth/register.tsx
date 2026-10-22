import { Head } from "@/lib/spa"
import { useState } from "react"
import { useNavigate } from "@tanstack/react-router"
import { LoaderCircle } from "lucide-react"
import GoogleLoginButton from "@/components/google-login-button"
import InputError from "@/components/input-error"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { useApp } from "@/contexts/AppContext"
import axios from "@/lib/axios"
import toast from "@/lib/toast"
import { invalidateAuth } from "@/middleware/auth"
import { login } from "@/routes"
import { store } from "@/routes/register"

export default function Register() {
	const { setLocalStorage } = useApp()
	const navigate = useNavigate()

	const [processing, setProcessing] = useState(false)
	const [errors, setErrors] = useState<Record<string, string>>({})

	function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
		event.preventDefault()
		setProcessing(true)
		setErrors({})
		const fd = new FormData(event.currentTarget)

		const { url, method } = store()

		axios
			.request({
				url,
				method,
				data: { ...Object.fromEntries(fd), device_name: "web" },
			})
			.then((response) => {
				setLocalStorage("sanctumToken", response.data.data)
				invalidateAuth()
				toast.success("Welcome to Black Music")
				navigate({ to: "/" })
			})
			.catch((err: unknown) => {
				const e = err as {
					response?: {
						status?: number
						data?: { errors?: Record<string, string | string[]> }
					}
				}

				if (e.response?.status === 422) {
					const raw = e.response.data?.errors ?? {}
					setErrors(
						Object.fromEntries(
							Object.entries(raw).map(([k, v]) => [
								k,
								Array.isArray(v) ? String(v[0] ?? "") : String(v),
							])
						)
					)
				} else if (e.response?.status === 429) {
					toast.error("Too many attempts. Please wait a minute and try again.")
				}
			})
			.finally(() => {
				setProcessing(false)
			})
	}

	return (
		<>
			<Head title="Create an account" />

			<div className="flex flex-col gap-6">
				<GoogleLoginButton />

				<div className="flex items-center gap-3 text-xs text-muted-foreground">
					<span className="h-px flex-1 bg-border" />
					or
					<span className="h-px flex-1 bg-border" />
				</div>

				<form
					onSubmit={handleSubmit}
					className="grid gap-4">
					<div className="grid gap-2">
						<Input
							id="name"
							label="Name"
							type="text"
							name="name"
							autoComplete="name"
							autoFocus
							required
						/>
						<InputError message={errors.name} />
					</div>

					<div className="grid gap-2">
						<Input
							id="email"
							label="Email"
							type="email"
							name="email"
							autoComplete="email"
							required
						/>
						<InputError message={errors.email} />
					</div>

					<div className="grid gap-2">
						<Input
							id="password"
							label="Password"
							type="password"
							name="password"
							autoComplete="new-password"
							required
						/>
						<InputError message={errors.password} />
					</div>

					<div className="grid gap-2">
						<Input
							id="password_confirmation"
							label="Confirm password"
							type="password"
							name="password_confirmation"
							autoComplete="new-password"
							required
						/>
						<InputError message={errors.password_confirmation} />
					</div>

					<Button
						className="w-full"
						disabled={processing}>
						{processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
						Create account
					</Button>
				</form>

				<div className="space-x-1 text-center text-sm text-muted-foreground">
					<span>Already have an account?</span>
					<Link
						href={login().url}
						variant="text"
						size="none">
						Log in
					</Link>
				</div>
			</div>
		</>
	)
}

Register.layout = {
	title: "Create an account",
	description: "Continue with Google, or sign up with your name and email",
}
