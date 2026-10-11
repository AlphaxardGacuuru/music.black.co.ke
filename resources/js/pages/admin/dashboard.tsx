import { Clapperboard, Headphones, MicVocal, ShoppingBag, Users } from "lucide-react"
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
