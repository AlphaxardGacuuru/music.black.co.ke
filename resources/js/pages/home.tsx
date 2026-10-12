import { HomeFeed } from "@/components/feed/home-feed"
import { Head } from "@/lib/spa"

export default function Home() {
	return (
		<>
			<Head title="Home | Black Music" />
			<HomeFeed />
		</>
	)
}
