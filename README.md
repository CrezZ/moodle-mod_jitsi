Forked from https://github.com/SergioComeron/moodle-mod_jitsi
Special for jitsi in Docker - https://github.com/CrezZ/docker-jitsi

CHANGES
- add Russian language
- add JWT token support - https://github.com/jitsi/lib-jitsi-meet/blob/master/doc/tokens.md
- add settings for JWT token auth - jitsi-meet-tokens
- add settigns for moderator mod jitsi - https://github.com/nvonahsen/jitsi-token-moderation-plugin
- add Firstname and Lastname to view name in Jitsi
- add some interaction


This module allows creating jitsi-meet videoconference sessions. If you have a jitsi-meet videoconference server, with this add-on you can create videoconference sessions in a course. You just have to configure the server domain (ip or url) and then in the course create a new Jitsi activity. It allows to create jitsi sessions to open on a specific date.


Atention:
- It is necessary to have a jitsi server (all-in-one doker with jitsi+token+JWT+moderator - https://github.com/CrezZ/docker-jitsi ).
- The microphone and camera only work on https.
