# queueSMS
sample PHP application using redis &amp; dockerfile

## Features

- HTTP API to insert an SMS Message in the queue
- HTTP API to consume an SMS Message from the queue and returns it in JSON format (FIFO)
- HTTP API to get the total number of messages in the queue
- HTTP API to get all SMS messages in the queue in JSON format
- Have everything running without any dependency on an external system (database, service, etc.)

## Docker

This project is very easy to install and deploy in a Docker container.

By default, the Docker will expose port 8000, so change this within the
Dockerfile if necessary.

```sh
cd queueSMS
docker compose up
```
**Just like that, You're good to go!**
