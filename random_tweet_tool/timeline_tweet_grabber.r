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

# Now you can search for tweets from a user's timeline
# Replace "username" with the actual username
tweets <- user_timeline("<USERNAME>", n = 3200)

# The "tweets" object now contains the tweets from the first page of the user's timeline
# Next, we'll use pagination to retrieve the remaining tweets
while(!is.null(tweets)) {
  # Get the next page of tweets
  next_tweets <- next_tweets(tweets)
  # Append the new tweets to the existing tweets
  tweets <- rbind(tweets, next_tweets)
}

# The "tweets" object now contains all of the tweets from the user's timeline
# You can now manipulate and analyze the tweets as desired
# The tweets are returned in reverse chronological order, which means the most recent # tweets will be returned first. You can also use additional parameters like
# exclude_replies, include_rts, trim_user, etc. to filter the tweets returned. rate limit of
# twitter API which is 450 requests per 15 minutes window for a user account.
