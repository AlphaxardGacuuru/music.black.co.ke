import {
	Activity,
	Clapperboard,
	Headphones,
	MicVocal,
	ShoppingBag,
	Users,
} from "lucide-react"
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts"
import { Head } from "@/lib/spa"
import AdminStatCard from "@/components/admin/AdminStatCard"
import Heading from "@/components/heading"
import {
	ChartContainer,
	ChartTooltip,
	ChartTooltipContent,
	type ChartConfig,
} from "@/components/ui/chart"
import { Card, CardContent } from "@/components/ui/card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useAdminDashboard } from "@/queries/admin"

const chartConfig: ChartConfig = {
	sold: { label: "Songs sold", color: "hsl(var(--chart-1))" },
}

export default function AdminDashboard() {
	const { data, isLoading } = useAdminDashboard()

	return (
		<>
			<Head title="Admin dashboard" />

			<div className="space-y-6">
				<Heading
					variant="small"
					title="Overview"
					description="Music activity across the app"
				/>

				{isLoading || !data ? (
					<div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
						{Array.from({ length: 8 }).map((_, index) => (
							<Skeleton
								key={index}
								className="h-20"
							/>
						))}
					</div>
				) : (
					<>
						<div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
							<AdminStatCard
								label="Total users"
								value={data.totals.totalUsers}
								icon={Users}
							/>
							<AdminStatCard
								label="Musicians"
								value={data.totals.totalMusicians}
								icon={MicVocal}
							/>
							<AdminStatCard
								label="Videos"
								value={data.totals.totalVideos}
								icon={Clapperboard}
							/>
							<AdminStatCard
								label="Audios"
								value={data.totals.totalAudios}
								icon={Headphones}
							/>
							<AdminStatCard
								label="Songs sold"
								value={data.totals.totalSales}
								icon={ShoppingBag}
								tone="success"
							/>
						</div>

						<ActiveUsersCard counts={data.activeUsers} />

						<div className="rounded-lg border p-4">
							<Heading
								variant="small"
								title="Songs sold, last 14 days"
							/>
							<ChartContainer
								config={chartConfig}
								className="h-64">
								<BarChart data={data.dailyVolume}>
									<CartesianGrid
										vertical={false}
										strokeDasharray="3 3"
									/>
									<XAxis
										dataKey="date"
										tickFormatter={(value: string) =>
											new Date(value).toLocaleDateString(undefined, {
												month: "short",
												day: "numeric",
											})
										}
										tickLine={false}
										axisLine={false}
									/>
									<YAxis
										allowDecimals={false}
										tickLine={false}
										axisLine={false}
									/>
									<ChartTooltip content={<ChartTooltipContent />} />
									<Bar
										dataKey="sold"
										fill="var(--color-sold)"
										radius={4}
									/>
								</BarChart>
							</ChartContainer>
						</div>
					</>
				)}
			</div>
		</>
	)
}

/** Users who used the app recently, linking through to the logins page. */
function ActiveUsersCard({
	counts,
}: {
	counts: { day: number; week: number; month: number }
}) {
	const periods = [
		{ label: "Last 24 hours", value: counts.day },
		{ label: "Last 7 days", value: counts.week },
		{ label: "Last 30 days", value: counts.month },
	]

	return (
		<Card>
			<CardContent className="space-y-4">
				<div className="flex items-center justify-between gap-4">
					<div className="flex items-center gap-3">
						<div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
							<Activity className="size-5" />
						</div>
						<p className="text-sm font-medium">Active users</p>
					</div>
					<Link
						href="/admin/logins"
						variant="muted"
						size="none">
						See logins
					</Link>
				</div>
				<dl className="grid grid-cols-3 gap-4">
					{periods.map((period) => (
						<div
							key={period.label}
							className="flex flex-col-reverse">
							<dt className="text-sm leading-tight text-muted-foreground">
								{period.label}
							</dt>
							<dd className="text-2xl font-semibold tracking-tight tabular-nums">
								{period.value.toLocaleString()}
							</dd>
						</div>
					))}
				</dl>
			</CardContent>
		</Card>
	)
}
