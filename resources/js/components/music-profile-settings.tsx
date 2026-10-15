import { useQueryClient } from "@tanstack/react-query"
import { useState, type FormEvent } from "react"
import Heading from "@/components/heading"
import { FileUpload } from "@/components/music/file-upload"
import { Button } from "@/components/ui/button"
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions, socialKeys } from "@/queries/social"
import type { User } from "@/types/auth"

const BIO_MAX = 50

/**
 * The music profile on the settings page: avatar, bio, minimum withdrawal
 * and becoming a musician. These save through the music API
 * (api/users/{id}), not the settings ProfileController.
 */
export default function MusicProfileSettings({ auth }: { auth: User }) {
	const queryClient = useQueryClient()
	const [bio, setBio] = useState(auth.bio ?? "")
	const [withdrawal, setWithdrawal] = useState("")
	const [saving, setSaving] = useState(false)
	const [upgrading, setUpgrading] = useState(false)
	const [confirmOpen, setConfirmOpen] = useState(false)

	const refreshUser = () => {
		void queryClient.invalidateQueries({ queryKey: ["auth"] })
		void queryClient.invalidateQueries({
			queryKey: socialKeys.profile(auth.username),
		})
		void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
	}

	const onSave = (event: FormEvent) => {
		event.preventDefault()
		setSaving(true)

		socialActions
			.updateUser(auth.id, {
				bio: bio.trim() || undefined,
				withdrawal: withdrawal || undefined,
			})
			.then((res) => {
				toast.success(res.data.message ?? "Account updated")
				setWithdrawal("")
				refreshUser()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	const onBecomeMusician = () => {
		setUpgrading(true)

		socialActions
			.updateUser(auth.id, { accountType: "musician" })
			.then(() => {
				toast.success("You're now a musician. Time to upload your first song.")
				setConfirmOpen(false)
				refreshUser()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setUpgrading(false))
	}

	const isMusician = auth.accountType === "musician"

	return (
		<>
			<div className="space-y-6">
				<Heading
					variant="small"
					title="Profile picture"
					description="Shown on your profile, posts and comments"
				/>
				<div className="flex items-center gap-4">
					<img
						src={auth.avatar}
						alt={auth.name}
						className="size-20 shrink-0 rounded-full border border-border object-cover"
					/>
					<FileUpload
						endpoint={`avatar/${auth.id}`}
						field="filepond-avatar"
						acceptedFileTypes={["image/*"]}
						maxFileSize="10MB"
						label='Drop a new picture or <span class="filepond--label-action">browse</span>'
						className="min-w-0 flex-1"
						onUploaded={() => {
							toast.success("Profile picture updated")
							refreshUser()
						}}
					/>
				</div>
			</div>

			<hr />

			{!isMusician && (
				<>
					<div className="space-y-6">
						<Heading
							variant="small"
							title="Become a musician"
							description="Upload videos and audios, post to your fans and get paid when they buy your songs."
						/>
						<Dialog
							open={confirmOpen}
							onOpenChange={setConfirmOpen}>
							<DialogTrigger asChild>
								<Button variant="outline">Become a musician</Button>
							</DialogTrigger>
							<DialogContent>
								<DialogHeader>
									<DialogTitle>Switch to a musician account?</DialogTitle>
									<DialogDescription>
										This can't be undone. Your account will stay a musician
										account, and we'll set up a "Singles" album for your videos
										and audios.
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<DialogClose asChild>
										<Button variant="ghost">Cancel</Button>
									</DialogClose>
									<Button
										onClick={onBecomeMusician}
										disabled={upgrading}>
										{upgrading ? <Spinner /> : "Yes, make me a musician"}
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
					</div>

					<hr />
				</>
			)}

			<div className="space-y-6">
				<Heading
					variant="small"
					title="Music profile"
					description="Your bio and when your earnings are paid out"
				/>
				<form
					onSubmit={onSave}
					className="space-y-6">
					<Input
						label="Bio"
						value={bio}
						maxLength={BIO_MAX}
						onChange={(event) => setBio(event.target.value)}
						helperText={`${bio.length}/${BIO_MAX}`}
					/>

					<div className="space-y-2">
						<Input
							label={`Minimum cash withdrawal (now KES ${auth.withdrawal ?? "1000"})`}
							type="number"
							min={0}
							inputMode="numeric"
							value={withdrawal}
							onChange={(event) => setWithdrawal(event.target.value)}
						/>
						<p className="text-sm text-muted-foreground">
							Setting it below{" "}
							<span className="font-semibold text-primary">KES 1000</span> adds
							a <span className="font-semibold text-primary">KES 50</span>{" "}
							charge from our provider on each withdrawal.
						</p>
					</div>

					<div className="flex items-center justify-end gap-4">
						<Button
							type="submit"
							disabled={saving}>
							Save
							{saving && <Spinner />}
						</Button>
					</div>
				</form>
			</div>
		</>
	)
}
