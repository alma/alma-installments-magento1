Changelog
=========

v1.0.1
------

Let's start following semver.

* Adds User-Agent string containing the module's version, PrestaShop version, PHP client and PHP versions, to all 
requests going to Alma's API.
* Switches logo image file to SVG

v1.0.0
------

This version evolved for a while without any version bump 🤷‍♂️  
Features in the latest push to this release:

* A message displays below the cart to indicate whether the purchase is eligible to monthly installments
* An "Eligibility Widget" can be used to insert the same message inside the mini cart, or into other hooking points 
* The module adds a payment method to the checkout, which redirects the user to Alma's payment page.  
If everything goes right (i.e. Customer doesn't cancel, pays the right amount, ... ), the created order is validated
upon customer return.
* An IPN controller is called from Alma's API to ensure order validation when the customer wouldn't return to the shop
