To release a new version of a plugin, we suggest the following checklist. Checking these locally before pushing to the repository gives you immediate feedback, instead of waiting for the repository to automatically run the checks.


- [ ] update the information.version in manifest.json; please use [semantic versioning](https://semver.org/#faq) ("major.minor.patch", e.g. "1.4.2" - major can break compatibility, minor can introduce compatible new features, patch is for bug fixes)
- [ ] in the project root directory, run `bash php-cs-check.sh` to check for valid PHP code
  - normally, this should report no errors
  - if any found, please fix - these could prevent your plugin from working
- [ ] in the `plugins/` directory, run `php php ../pack-plugin.php $YOUR_PLUGIN`, where $YOUR_PLUGIN is the directory name of your plugin; this creates the installable package
  - if you have a `composer.json`, this will check it and install the dependencies - if this reports as "outdated", please fix by running `composer update` in your plugin's directory
  - normally, this should finish without errors or warnings
- [ ] in the project root directory, run `php validate.php`
  - normally, this should report 0 errors
  - if it complains that `plugins.json` is outdated, run `php generate-json.php > plugins.json` to update it.
- [ ] commit the changes made during these steps 
