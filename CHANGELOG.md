Changelog
=========

v2.1.1
------

* Add PT Translation
* Fix bug on first install


v2.1.0
------

* Pay >4 and Paylater
* Eligibility V2
* Widget on product page
* Widget on cart page

v2.0.0
------

* Add i18n

v1.2.1
------

* Fix some French missing translations

v1.2.0
------

* Dependencies update
* Code cleaning
* Fix & improve handling of eligibility when several installments plans are activated
* Fix & improve API key validation and saving: only the key for selected API mode is now required
* Include order "increment ID" (i.e. order reference) in Alma's payment data – will be available in Alma data exports 

v1.1.1
------

* Fixes bug that leads to a blank configuration page on first-time installation

v1.1.0
------

* Dependencies update
* Adds support for different installments plans: 2-, 3- and 4-installment plans, configurable in the Payment Methods 
  settings.

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
