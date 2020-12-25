Substituting user attributes into the message subject
-------

Use the "patch" command to apply this patch against your PHPList source code. 

I.e., if you've just downloaded and unpacked PHPList v2.10.12, and a folder "phplist-2.10.12" has been created, from the directory outside of that folder, run:

patch -p0 < phplist-2.10.12-attribute-substitution-in-subject.patch