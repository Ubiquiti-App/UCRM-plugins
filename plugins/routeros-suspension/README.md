# Suspension plugin for Mikrotik RouterOS
This plugin synchronizes list of suspended IP addresses from UCRM to RouterOS based router. 


## Configuration
To enable suspension page redirect, you must manually configure _Filter Rules_ and _NAT_ firewall rules.

### RouterOs Filter Rules
- For chain `input` create `accept` action for UNMS IP address.
- For chain `forward` create `accept` action for UNMS IP address.
- For chain `forward` create `jump` action to `jump-target` named eg. `ucrm_forward_general`.
- For chain `forward` create `jump` action to `jump-target` named eg. `ucrm_forward_drop`.
- For chain `ucrm_forward_general` create action `accept`  DNS (`protocol` set to `udp` and `dst-port` to `53`) for `BLOCKED_USERS` Address List
- For chain `ucrm_forward_drop` create action `drop`to drop requests with different IP addresses than UNMS IP (`dst-address` is not UNMS IP address) for `BLOCKED_USERS` Address List

### NAT Rules
- For chain `dstnat` crate `jump` action with `jump-target` set to  `ucrm_first_dstnat` 
- For chain `ucrm_first_dstnat` create `dst-nat` action for `src-address-list` list `BLOCKED_USERS`, `dst-port` set to 
`80`, `to-addresses` set to UNMS IP address and `to-ports` set to port suspension page port.
