# First, install and load the "rtweet" library
install.packages("rtweet")
library(rtweet)

# Next, authenticate with Twitter using your API keys
# You can get your API keys from the Twitter developer website
# Replace YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, YOUR_ACCESS_TOKEN, and YOUR_ACCESS_SECRET with your actual API keys
setup_twitter_oauth(consumer_key = "<YOUR_CONSUMER_KEY>",
                    consumer_secret = "<YOUR_CONSUMER_SECRET>",
                    access_token = "<YOUR_ACCESS_TOKEN>",
                    access_secret = "<YOUR_ACCESS_SECRET>")

# Search for tweets containing the phrase "data science"
tweets <- search_tweets("data science", n = 100)

# Get the ID of the oldest tweet returned in the first request
oldest_id <- min(tweets$status_id)

# Create a loop to retrieve tweets until there are no more tweets to retrieve
while(length(tweets) > 0) {
  # Search for tweets containing the phrase "data science" and with an ID less than or equal to the oldest tweet returned in the previous request
  tweets <- search_tweets("data science", max_id = oldest_id, n = 100)
  # Update the oldest tweet ID
  oldest_id <- min(tweets$status_id)
}

# The "tweets" object now contains all tweets that match your search criteria
# You can now manipulate and analyze the tweets as desired
# https://github.com/ropensci/rtweet
