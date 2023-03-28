# Suspension plugin for Mikrotik RouterOS
This plugin synchronizes the list of suspended IP addresses from UNMS’ CRM to RouterOS based router. 

This plugin is compatible with UNMS v2.1.0 and higher.

The plugin synchronizes the suspended IPs and the suspension rules (NAT, Firewall) based on the frequency set in the plugin configuration. (This might be improved in the future, for example, this plugin could be triggered by webhooks monitoring the client’s service status.)

Make sure that IP addresses to be suspended belong to the `Monitored IP subnets` defined in the UNMS' Network settings. Only those client IP addresses are added to the block list when the client gets suspended. 

To all developers: feel free to extend or improve this plugin and share your code with others. You might find a better way how to handle the sync of the suspension rules or sync of the blocked IPs.
