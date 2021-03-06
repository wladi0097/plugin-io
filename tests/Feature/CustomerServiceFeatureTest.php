<?php

namespace IO\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use IO\Builder\Order\AddressType;
use IO\Constants\SessionStorageKeys;
use IO\Services\BasketService;
use IO\Services\CustomerService;
use IO\Services\SessionStorageService;
use IO\Tests\TestCase;
use Plenty\Modules\Account\Address\Models\Address;

class CustomerServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @var CustomerService $customerService */
    protected $customerService;

    protected $genders = ['male', 'female'];

    protected function setUp()
    {
        parent::setUp();

        $this->customerService = pluginApp(CustomerService::class);
    }

    /**
     * @test
     * @dataProvider createAddressProvider
     * @param $addressData
     * @param $addressType
     */
    public function should_add_a_new_address_as_guest($addressData, $addressType)
    {
        $this->createAddress($addressData, $addressType);
    }

    /**
     * @test
     * @dataProvider createAddressProvider
     * @param $addressData
     * @param $addressType
     */
    public function should_add_a_new_address_as_logged_in_user($addressData, $addressType)
    {
        $email    = $this->fake->email;
        $password = $this->fake->password;
        $this->createContact($email, $password);
        $this->performLogin($email, $password);
        $this->createAddress($addressData, $addressType);
    }


    private function createAddress($addressData, $addressType)
    {

        /**
         * @var $sessionStorage SessionStorageService
         */
        $sessionStorage = pluginApp(SessionStorageService::class);

        $sessionStorage->setSessionValue(SessionStorageKeys::GUEST_EMAIL, $this->fake->email);


        $newAddress = $this->customerService->createAddress($addressData, $addressType);

        $this->assertNotNull($newAddress);
        $this->assertInstanceOf(Address::class, $newAddress);

        $this->assertAddressFieldsAreEqual($addressData, $newAddress);

        if ($addressData['address1'] == 'POSTFILIALE') {
            $this->assertTrue($newAddress->isPostfiliale);
        } elseif ($addressData['address1'] == 'PACKSTATION') {
            $this->assertTrue($newAddress->isPackstation);
        }
    }

    /**
     * @test
     * @dataProvider deleteAddressProvider
     * @param $addressData
     * @param $addressType
     */
    public function should_delete_an_address_as_guest($addressData, $addressType)
    {
        $this->deleteAddress($addressData, $addressType);
    }

    /**
     * @test
     * @dataProvider deleteAddressProvider
     * @param $addressData
     * @param $addressType
     */
    public function should_delete_an_address_as_logged_in_user($addressData, $addressType)
    {
        $email    = $this->fake->email;
        $password = $this->fake->password;
        $this->createContact($email, $password);
        $this->performLogin($email, $password);
        $this->deleteAddress($addressData, $addressType);
    }

    private function deleteAddress($addressData, $addressType)
    {

        /**
         * @var $sessionStorage SessionStorageService
         */
        $sessionStorage = pluginApp(SessionStorageService::class);

        $sessionStorage->setSessionValue(SessionStorageKeys::GUEST_EMAIL, $this->fake->email);

        /** @var BasketService $basketService */
        $basketService = pluginApp(BasketService::class);

        $address = $this->customerService->createAddress($addressData, $addressType);
        $this->customerService->deleteAddress($address->id, $addressType);

        if ($addressType == AddressType::BILLING) {
            $this->assertEquals($basketService->getBillingAddressId(), 0);
        } elseif ($addressType == AddressType::DELIVERY) {
            $this->assertNull($basketService->getDeliveryAddressId());
        }
    }

    /**
     * @test
     * @dataProvider updateAddressProvider
     * @param $addressDataCreate
     * @param $addressDataUpdate
     * @param $addressType
     */
    public function should_update_an_address_as_guest($addressDataCreate, $addressDataUpdate, $addressType)
    {
        $this->updateAddress($addressDataCreate, $addressDataUpdate, $addressType);
    }

    /**
     * @test
     * @dataProvider updateAddressProvider
     * @param $addressDataCreate
     * @param $addressDataUpdate
     * @param $addressType
     */
    public function should_update_an_address_as_logged_in_user($addressDataCreate, $addressDataUpdate, $addressType)
    {
        $email    = $this->fake->email;
        $password = $this->fake->password;
        $this->createContact($email, $password);
        $this->performLogin($email, $password);
        $this->updateAddress($addressDataCreate, $addressDataUpdate, $addressType);
    }

    private function updateAddress($addressDataCreate, $addressDataUpdate, $addressType)
    {

        /**
         * @var $sessionStorage SessionStorageService
         */
        $sessionStorage = pluginApp(SessionStorageService::class);

        $sessionStorage->setSessionValue(SessionStorageKeys::GUEST_EMAIL, $this->fake->email);

        $address        = $this->customerService->createAddress($addressDataCreate, $addressType);
        $updatedAddress = $this->customerService->updateAddress($address->id, $addressDataUpdate, $addressType);

        $this->assertNotNull($updatedAddress);
        $this->assertInstanceOf(Address::class, $updatedAddress);
        $this->assertEquals($address->id, $updatedAddress->id);
        $this->assertAddressFieldsAreEqual($addressDataUpdate, $updatedAddress);

        if ($addressDataUpdate['address1'] == 'POSTFILIALE') {
            $this->assertTrue($updatedAddress->isPostfiliale);
        } elseif ($addressDataUpdate['address1'] == 'PACKSTATION') {
            $this->assertTrue($updatedAddress->isPackstation);
        }
    }

    public function createAddressProvider()
    {
        return [
            [
                [
                    // Billing address with company and empty gender and stateId
                    'gender'     => '',
                    'name1'      => $this->fake->company,
                    'name2'      => '',
                    'name3'      => '',
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'stateId'    => '',
                    'contactPerson' => $this->fake->name
                ],
                AddressType::BILLING,
            ],

            [
                [
                    // Billing address
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => '',
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                AddressType::BILLING,
            ],

            [
                [
                    // Delivery address with company
                    'gender'     => '',
                    'name1'      => $this->fake->company,
                    'name2'      => '',
                    'name3'      => '',
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'contactPerson' => $this->fake->name
                ],
                AddressType::DELIVERY,
            ],

            [
                [
                    // Delivery address to 'Packstation'
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => 'PACKSTATION',
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                ],
                AddressType::DELIVERY,
            ],

            [
                [
                    // Delivery address to 'Postfiliale'
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => 'POSTFILIALE',
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                ],
                AddressType::DELIVERY,
            ]
            // TODO Address Options
        ];
    }

    public function deleteAddressProvider()
    {
        return [
            [
                [
                    // Billing address with company
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                ],
                AddressType::BILLING,
            ],

            [
                [
                    // Billing address with company
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                ],
                AddressType::DELIVERY,
            ],
        ];
    }

    public function updateAddressProvider()
    {
        return [
            [
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => 'change',
                    'name2'      => 'change',
                    'name3'      => 'change',
                    'name4'      => 'change',
                    'address1'   => 'change',
                    'address2'   => 'change',
                    'postalCode' => 'change',
                    'town'       => 'change',
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => $this->fake->streetName,
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                AddressType::BILLING,
            ],

            [
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => 'change',
                    'name2'      => 'change',
                    'name3'      => 'change',
                    'name4'      => 'change',
                    'address1'   => 'change',
                    'address2'   => 'change',
                    'postalCode' => 'change',
                    'town'       => 'change',
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => 'PACKSTATION',
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                AddressType::DELIVERY,
            ],

            [
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => 'change',
                    'name2'      => 'change',
                    'name3'      => 'change',
                    'name4'      => 'change',
                    'address1'   => 'change',
                    'address2'   => 'change',
                    'postalCode' => 'change',
                    'town'       => 'change',
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                [
                    'gender'     => $this->fake->randomElement($this->genders),
                    'name1'      => $this->fake->company,
                    'name2'      => $this->fake->firstName,
                    'name3'      => $this->fake->lastName,
                    'name4'      => '',
                    'address1'   => 'POSTFILIALE',
                    'address2'   => $this->fake->streetAddress,
                    'postalCode' => $this->fake->postcode,
                    'town'       => $this->fake->city,
                    'countryId'  => 1,
                    'stateId'    => '',
                ],
                AddressType::DELIVERY,
            ],
        ];
    }

    private function assertAddressFieldsAreEqual($address1, $address2)
    {
        foreach ($address1 as $key => $value) {
            // Do not compare 'contactPerson' because it is stored as a address option
            if ($key !== 'contactPerson') {
                $this->assertEquals($address1[$key], $address2[$key]);
            }
        }
    }
}
