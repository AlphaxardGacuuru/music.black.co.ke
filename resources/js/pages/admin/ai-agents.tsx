import { Head } from "@/lib/spa"
import Heading from "@/components/heading"
import { Card, CardContent } from "@/components/ui/card"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import toast from "@/lib/toast"
import { useAdminAiAgents, useToggleAiAgents } from "@/queries/admin"

const AGENTS = [
	{
		name: "Local artist news",
		description:
			"Summarises local music news at 8am, 1pm and 6pm. When off, news is still posted as just the headline and source.",
	},
	{
		name: "Daily story research",
		description:
			"Finds upcoming gigs and music history for the 7am story. When off, the story only has headlines and trending songs.",
	},
]

export default function AdminAiAgents() {
	const { data, isLoading } = useAdminAiAgents()
	const toggle = useToggleAiAgents()

	function handleToggle(enabled: boolean) {
		toggle.mutate(enabled, {
			onSuccess: (response) => toast.success(response.message),
			onError: () => toast.error("Couldn't change the AI agents switch"),
		})
	}

	return (
		<>
			<Head title="AI agents" />

			<div className="space-y-6">
				<Heading
					variant="small"
					title="AI agents"
					description="Turn the scheduled AI agents on or off to control AI usage"
				/>

				<Card>
					<CardContent className="space-y-6">
						{isLoading || !data ? (
							<Spinner />
						) : (
							<>
								<div className="flex items-center justify-between gap-4">
									<div className="space-y-1">
										<p className="font-medium">Run AI agents</p>
										<p className="text-sm text-muted-foreground">
											{data.enabled
												? "The agents run on their schedules."
												: "No AI calls are made. Scheduled posts go out without AI content."}
										</p>
									</div>

									{/* Enabled Start */}
									<Switch
										checked={data.enabled}
										disabled={toggle.isPending}
										onCheckedChange={handleToggle}
										aria-label="Run AI agents"
									/>
									{/* Enabled End */}
								</div>

								{!data.configured && (
									<p className="rounded-md bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400">
										No AI API key is set on the server, so the agents won't run
										even when switched on.
									</p>
								)}

								<ul className="space-y-3 border-t pt-4">
									{AGENTS.map((agent) => (
										<li key={agent.name}>
											<p className="text-sm font-medium">{agent.name}</p>
											<p className="text-sm text-muted-foreground">
												{agent.description}
											</p>
										</li>
									))}
								</ul>
							</>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	)
}
