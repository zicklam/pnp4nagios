# pnp4nagios



## Usage inside icinga|nagios service
`check_nagiostats!--TIMERANGE 5`

### Template installation path
`/usr/share/pnp4nagios/templates`
or in any other template path configured as 
```php
$conf['template_dirs'][] = 
``` in PNP_CONFIG_PATH/config.php

# Example output
![Alt Text](/.docs/check_latency.png)
![Alt Text](/.docs/check_execution_time.png)
![Alt Text](/.docs/service_performance.png)
![Alt Text](/.docs/host_performance.png)
![Alt Text](/.docs/service_statistics.png)
![Alt Text](/.docs/host_statistics.png)