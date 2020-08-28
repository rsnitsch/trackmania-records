# trackmania-records - Web application

This is the web application part of trackmania-records, a Trackmania 2020
records sharing tool. The web application is where all your records (and the
records of your friends) will be stored, displayed and compared to each other.

In the browser, it looks like this:

![Screenshot 1](https://github.com/rsnitsch/trackmania-records/raw/develop/screenshot1.png "Screenshot 1")

It also features a total time comparison:

![Screenshot 2](https://github.com/rsnitsch/trackmania-records/raw/develop/screenshot2.png "Screenshot 2")

## Requirements

- PHP with PDO/SQLite support
- Recommended webserver: Apache2. If you use another webserver like nginx,
  you should make sure that access to the file database.db is restricted. For Apache2,
  a .htaccess for that purpose is already included.

## Setup

Simply copy the files in the folder ``html`` to a webhoster/server of your choice.
