Provisioning API
================

This code provides a simple PHP interface to the Google Provisioning API. It is intended for command-line scripting of Google Apps administrative functions, and not for web-based usage.

You may also be interested in the Zend Google Data library. This code was put together because that project lacks support for organizational units, and is not being developed.

Requriements
------------
You will need:
- [PHP](http://php.net/) with the [cURL extension](http://php.net/manual/en/book.curl.php) loaded.
- A [Google Apps account](http://www.google.com/enterprise/apps/) with administrator access, on a domain with [API Access enabled](https://developers.google.com/google-apps/provisioning/#getting_started).

Note
----
Both of these APIs are deprecated, as of May 2013. At some point in the future, this project will be replaced by the newer Directory API.

APIs Used
---------
- Google Provisioning API
- Google ClientLogin
