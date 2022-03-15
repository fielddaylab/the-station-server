.PHONY: deploy

deploy:
	rsync -vrc * root@thestationmaine.com:/var/www/thestationmaine/server --exclude-from rsync-exclude

upgrade:
   @curl http://thestationmaine.com/server/json.php/v2.db.upgrade --silent --data '{}' | tr -d '\r\n'
