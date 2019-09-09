deploy_sample/deployer/deployer.phar:
	cd deploy_sample/deployer && curl -LO https://deployer.org/deployer.phar
	chmod +x deploy_sample/deployer/deployer.phar

# uzulla作業用
.PHONY: uzulla-local-reset-all
uzulla-local-reset-all:
	find . | grep .DS_Store |xargs rm
	-rm environment-setup/deployer/deployer.phar
	git status --ignored
