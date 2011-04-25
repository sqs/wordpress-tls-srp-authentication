dump_mysql:
	mysqldump -u root wordpress_trustedhttp_org > site-conf/wordpress_trustedhttp_org.sql

copy_apache_conf:
	cp /etc/apache2/sites-available/wordpress.trustedhttp.org site-conf/