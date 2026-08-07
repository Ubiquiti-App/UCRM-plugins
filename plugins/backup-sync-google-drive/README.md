# Backup synchronization - Google Drive
This plugin handles synchronization of your UNMS backups to a folder on a Google Drive Shared Drive, via a Google Workspace service account.  
Please note, that backups deleted from UNMS are deleted from Google Drive as well.

## Configuration
### Execution period
Set up an execution period of 24 hours if you want to synchronize your backups automatically. UNMS generates a backup once per day, so 24 hours is more than enough.

### UNMS API token
You can create a UNMS API token in Network -> Settings -> Users. It must belong to a Super Admin user - the backups endpoint this plugin reads from is restricted to Super Admins. A token from a lesser-privileged user will authenticate fine but the plugin will find no backups to sync, without any error.

### Google Cloud / Workspace setup
This plugin authenticates as a service account rather than a personal Google account, so there's no OAuth "click here to authorize" step and no refresh token that can expire from inactivity. Storage is pooled on a Shared Drive, not tied to one employee's account.

1. In the [Google Cloud Console](https://console.cloud.google.com/), create or choose a project, then enable the **Google Drive API** for it (APIs & Services -> Library -> search "Google Drive API" -> Enable).
2. Go to APIs & Services -> Credentials -> Create Credentials -> Service Account. Give it any name, e.g. "UISP backup sync".
3. Open the new service account, go to the "Keys" tab, Add Key -> Create new key -> JSON. This downloads a JSON key file - paste its full contents into the plugin's "Google service account JSON key" field.
4. In Google Drive, create (or choose) a **Shared Drive** to hold the backups. Shared Drives require a Google Workspace account - a personal Google account cannot create one.
5. Add the service account as a member of the Shared Drive (Shared Drive -> Manage members -> add the service account's email address, found on its Cloud Console page, with Content Manager access or higher).
6. Copy the Shared Drive's ID from its URL (`drive.google.com/drive/folders/<id>` when viewing the Shared Drive's root) into the plugin's "Google Shared Drive ID" field.
7. Optionally set a "Backup folder name" - this folder is created automatically at the root of the Shared Drive on first run. Defaults to "UISP Backups" if left blank.

The backups will then appear in that folder on the Shared Drive.
