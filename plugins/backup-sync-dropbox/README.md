# Backup synchronization - Dropbox
This plugin handles synchronization of your UNMS backups to a Dropbox folder.  
Please note, that backups deleted from UNMS are deleted from Dropbox as well.

## Configuration
### Execution period
Set up an execution period of 24 hours if you want to synchronize your backups automatically. UNMS generates a backup once per day, so 24 hours is more than enough.

### UNMS API token
You can create a UNMS API token in Network -> Settings -> Users.

### Dropbox access token
To generate an access token:
1. Go to the Dropbox App console - https://www.dropbox.com/developers/apps
2. Click the "Create app" button.
3. Choose the "Dropbox API" option.
4. Choose the "App folder" access type.
5. Enter any name you want for the app, e.g. "UNMS backups".
6. Submit the "Create app" form.
7. You will be redirected to the app info page. Scroll to the "Generated Access Token", click "Generate" and copy the value into the plugin configuration.
