Dspace REST API Harvester
=========================

The __Dspace REST API Harvester plugin__ imports records from Dspace REST API data providers.
Currently, it supports the Dspace 4.x REST API. It uses guzzle, a PHP HTTP client
and framework, as RESTful web service clients [http://guzzlephp.org/].

Using this plugin, you can upload your customized metadata -
they can be the extensions of Dublin Core and your self-defined schema. Using the elements.ini
to config and map the metadata to Omeka data model.

The plugin only imports the metadata and thumbnail images if available, 
but allows you to link the bitstreams from Dspace to Omeka items. The plugin can be used for 
one-time data transfers, or to keep up-to-date with changes to an online repository.

The future plan includes more flexible metadata config (add/remove from UI)
and bitstream copy/link options. Also, the branch DspaceRestapiHaverster-wijiti supports Dspace 3.x 
with wijiti REST API.

Configuration
-------------

* __Path to PHP-CLI__: Path to your server's PHP-CLI command. The PHP version
  must correspond to normal Omeka requirements. Some web hosts use PHP 4.x for
  their default PHP-CLI, but many provide an alternative path to a PHP-CLI
  5 binary. Check with your web host for more information.
* __Memory Limit__: Set a memory limit to avoid memory allocation errors during
  harvesting. We recommend that you choose a high memory limit. Examples
  include 128M, 1G, and -1. The available options are K (for Kilobytes), M (for
  Megabytes) and G (for Gigabytes). Anything else assumes bytes. Set to -1 for
  an infinite limit. Be advised that many web hosts set a maximum memory limit,
  so this setting may be ignored if it exceeds the maximum allowable limit.
  Check with your web host for more information.
* __Install or Enable PHP-cURL module__: Please install or enable your cURL module
  if it is not installed or enabled.
* __Install or Enable PHP-json module__: Please install or enable your json module
  if it is not installed or enabled.


Instructions
------------

### Performing a harvest

* Go to Admin > "Dspace REST API Harvester"
* Enter an Dspace REST API base URL, click "View Collections"
* Select a set and click "Harvest"
* The harvest process runs in the background and may take a while
* Refresh the page to check the progress
* If you encounter errors, submit the base URL and status messages to the Omeka forums

### Re-harvesting and updating 
The harvester includes the ability to make multiple successive harvests from
a single repository, keeping in sync with changes to that repository.

After a collection has been successfully harvested, a "Re-harvest"
button will be added to its entry on the Admin > Dspace REST API Harvester page.
Clicking this button will harvest from that repository again using all the same
settings, adding new items and updating previously-harvested items as
necessary.

Manually specifying the exact same harvest to be run again (same base URL, collection
handle) will result in the same behavior.






