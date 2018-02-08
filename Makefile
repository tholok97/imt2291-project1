build: 
	docker build -t imt2291project1image .

rundb:
	docker run --name db -e MYSQL_ROOT_PASSWORD=secret923 -d mariadb:10.3.4

run: build
	docker run --link db -d --name wwwtestcontainer -v ${PWD}:/var/www/html -p 80:80 imt2291project1image

bash:
	docker exec -it wwwtestcontainer /bin/bash

stop:
	docker stop wwwtestcontainer

remove: stop
	docker rm wwwtestcontainer
