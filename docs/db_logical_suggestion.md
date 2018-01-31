# Logical design of the db

Based on project task description.

## The structure is as follows

```
<tablename> (<columnname>, <columnname>, ...)
PRIMARY KEY <columnname>
FOREIGN KEY <columnname> REFERENCES <tablename>.<columnname>
....
```

## My suggestion (Thomas)

```
User (uid, username, firstname, lastname, password_hash, privilege_level)
PRIMARY KEY uid

Video (vid, title, description, thumbnail, rating, uid, topic, course_code, timestamp)
PRIMARY KEY vid
FOREIGN KEY uid REFERENCES User.uid

Playlist (pid, title, description, thumbnail)
PRIMARY KEY pid

Comment (cid, vid, uid, text, timestamp)
PRIMARY KEY cid
FOREIGN KEY vid REFERENCES Video.vid

In_playlist (vid, pid)
PRIMARY KEY vid, pid
FOREIGN KEY vid REFERENCES Video.vid
FOREIGN KEY pid REFERENCES Playlist.pid

Subscribes_to (uid, pid)
PRIMARY KEY uid, pid
FOREIGN KEY uid REFERENCES User.uid
FOREIGN KEY pid REFERENCES Playlist.pid
```

## Assumptions

* Only one lecturer uploads a video
* Multiple lecturers can contribute to the same playlist, in which case they will all have maintenance rights to it
    * You have maintenance rights IF: You have a video that is in the playlist. Can use joins to find this out.
* The only thing differentiating the different user types in the db is their privelege level (admin doesn't store more info than a student)
    * Privilege level is stored as an int 0, 1 or 2 *(?)*
* Thumbnails are stored directly in the database as small images
* 'Emne' is interpreted as 'Course' (:
* **!!**: Course-code and topic should really be in their own tables, but to cut some corners I've put them only in the Video table..

## Weak points

* It's not normalized. But making it 100% normalized means tons of time spent doing tedious database operations, so IMO it's fine for this project.
