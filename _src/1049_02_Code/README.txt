Recaptcha subscribe page code for PHPList

The file phplist-2.10.12-recaptcha-subscribe-form.patch is a "unified diff", intended to be patched against the original source code for PHPList 2.10.12

To patch the PHPList source code, do the following (on a linux host):

* Change to the directory one "above" the directory containing the unpacked PHPList source code. (i.e., there should be a subfolder named "phplist-2.10.12")
* Put the patch file in the same directory
* Run "patch -p1 < phplist-2.10.12-recaptcha-subscribe-form.patch" to merge the patch with the PHPList source code

Output will look something like this:

---
[root@testhost Downloads]# patch -p0 < phplist-2.10.12-recaptcha-subscribe-form.patch 
patching file phplist-2.10.12/public_html/lists/admin/subscribelib2.php
patching file phplist-2.10.12/public_html/lists/index.php
[root@testhost Downloads]#
---
