A few notes on the included code:

1. In the absence of a reliable post_exists function within the GData
   framework, this script uses a local MySQL database to store information
   about existing posts. When an article is inserted into the Blogger site,
   the database stores the Brafton article id and matches it to the Blogger
   article id assigned on insertion. Then, on future runs, the importer will
   search for the Brafton article id, find it, and update the correspoding 
   Blogger article (using the Blogger id) instead of inserting a new post.

2. This script is meant to be set up as a cron process on a high-uptime 
   machine. Brafton has resources to maintain this, although we would
   encourage the client to host it if possible.


For SEO folks and Account Managers:
Blogger does not like ampersands in Categories. Keep category text as simple as possible (avoid punctuation).

Use:
user$ php brafton_blogger.php
