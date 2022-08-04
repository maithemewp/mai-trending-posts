# Mai Trending Posts
Show total views and display popular or trending posts in Mai Post Grid. Uses Jetpack Stats.

## Display trending posts
Using Mai Post Grid, under the Entries tab, set "Get Entries By" setting to "Trending".

## Display the most viewed posts
Using Mai Post Grid, under the Entries tab, set "Order By" setting to "Views" and make sure "Order" setting is "Descending". Optionally show the most popular posts withing a timeframe but adding "7 days ago" to the "After date" setting.

## Bulk update posts via WP-CLI
Use `wp maitp update_views --post_type=post --posts_per_page=500 --offset=0` to update the most recent 500 posts. Add 500 to the `--offset` value and run again. Continue in increments of 500 until you see `No posts found.` letting you know you've updated all posts.
