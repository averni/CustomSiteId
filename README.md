# Matomo CustomSiteId Plugin

## Description

This experimental plugin allows the administrator to define a custom site id that can be used instead of the default numeric one, useful in auto provisioning/orchestrated scenarios when combined with the awesome [ExtraTools](https://github.com/digitalist-se/extratools) plugin (which is required for this plugin to work). 

It comes with a command line tool to set or get a custom site id via console. Ex:

`
php ./console customsiteid:set --name=example.com --custom-site-id=my-custom-site-id

php ./console customsiteid:get --custom-site-id=my-custom-site-id
`


## Warning

This plugin is experimental and may not work as expected, slow down your Matomo installation or mess up the tracking. Use at your own risk.

## Credits

Derived from this [CustomSiteId](https://github.com/wfreeman8/CustomSiteId/) plugin.
