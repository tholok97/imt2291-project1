build: 
	docker build -t wwwtest .

run: build
	docker run --link some-mariadb -d --name wwwtestcontainer -v ${PWD}:/var/www/html -p 80:80 wwwtest

bash:
	docker exec -it wwwtestcontainer /bin/bash

stop:
	docker stop wwwtestcontainer

remove: stop
	docker rm wwwtestcontainer
