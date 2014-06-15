SimplePHPTracker
================

A (very) minimalist PHP tracker for BitTorrent applications, simple to use and modify at will for your own purpose.
This application is a depurated fork from [php-tracker][1].


Installation
------------
To install the tracker just follow these steps:

- Drop the files into your FTP repo.
- Import the tracker_peers.sql file into your database.
- Check the configuration in the tracker.php file (configure the database access).
- You're done!

Now to use the tracker you just need to include the URL to the tracker.php file while creating a torrent, ie http://my.website.com/tracker.php
All the peers using this URL will get connected to each other.

  [1]: http://php-tracker.org/
