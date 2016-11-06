Please visit http://www.spambotsecurity.com/zbblock.php for further 
information, download and installation instructions.

*** There is a special customized version for use with Subdreamer 3.4.3+
*** available here: http://www.subdreamer.org/plugins.html
*** Please read the included readme file in the archive!

Note: the main file "zbblock.php" MUST reside in folder "includes/zbblock"!
The folder "vault" permissions must be set to 0777 before uploading files!

Once successfully installed edit the following file: /admin/branding.php 
If that does not exist, rename the existing file "branding.default.php".
Then add this line to the end of it and remove // to uncommen it:

//define('ZB_BLOCK', true); // uncomment to enable ZB Block: http://www.spambotsecurity.com/zbblock.php

Save the branding.php file and re-upload it to the admin folder.
Should then the frontpage not load or produce a white page, comment
that added line again!