<?php
declare(strict_types=1);

namespace Smsapi\Client\Tests\Integration\Feature\Sms;

use DateTime;
use Smsapi\Client\Feature\Contacts\Bag\CreateContactBag;
use Smsapi\Client\Feature\Contacts\Data\ContactGroup;
use Smsapi\Client\Feature\Contacts\Groups\Bag\AssignContactToGroupBag;
use Smsapi\Client\Feature\Contacts\Groups\Bag\CreateGroupBag;
use Smsapi\Client\Feature\Sms\Bag\ScheduleSmsBag;
use Smsapi\Client\Feature\Sms\Bag\ScheduleSmssBag;
use Smsapi\Client\Feature\Sms\Bag\ScheduleSmsToGroupBag;
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;
use Smsapi\Client\Feature\Sms\Bag\SendSmssBag;
use Smsapi\Client\Feature\Sms\Bag\SendSmsToGroupBag;
use Smsapi\Client\Tests\Fixture\PhoneNumberFixture;
use Smsapi\Client\Tests\Helper\ContactsHelper;
use Smsapi\Client\Tests\SmsapiClientIntegrationTestCase;

class SmsFeatureTest extends SmsapiClientIntegrationTestCase
{
    /**
     * @test
     */
    public function it_should_send_sms()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someReceiver = PhoneNumberFixture::validMobile();
        $sendSmsBag = SendSmsBag::withMessage($someReceiver, 'some message');
        $sendSmsBag->test = true;

        $result = $smsFeature->sendSms($sendSmsBag);

        $this->assertEquals($someReceiver, $result->number);
    }

    /**
     * @test
     */
    public function it_should_send_sms_with_external_id()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someReceiver = PhoneNumberFixture::validMobile();
        $externalId = 'any';
        $sendSmsBag = SendSmsBag::withMessage($someReceiver, 'some message');
        $sendSmsBag->setExternalId($externalId);
        $sendSmsBag->test = true;

        $result = $smsFeature->sendSms($sendSmsBag);

        $this->assertEquals($someReceiver, $result->number);
        $this->assertEquals($externalId, $result->idx);
    }

    /**
     * @test
     */
    public function it_should_receive_details_for_single_sms()
    {
        $message = 'some message';
        $smsFeature = self::$smsapiService->smsFeature();
        $someReceiver = PhoneNumberFixture::validMobile();
        $sendSmsBag = SendSmsBag::withMessage($someReceiver, $message);
        $sendSmsBag->test = true;

        $result = $smsFeature->sendSms($sendSmsBag);

        $this->assertNotNull($result->content);
        $this->assertEquals($message, $result->content->message);
        $this->assertEquals(mb_strlen($message), $result->content->length);
        $this->assertEquals(1, $result->content->parts);
    }

    /**
     * @test
     */
    public function it_should_send_flash_sms()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someReceiver = PhoneNumberFixture::validMobile();
        $sendFlashSmsBag = SendSmsBag::withMessage($someReceiver, 'some message');
        $sendFlashSmsBag->test = true;

        $result = $smsFeature->sendFlashSms($sendFlashSmsBag);

        $this->assertEquals($someReceiver, $result->number);
    }

    /**
     * @test
     */
    public function it_should_send_sms_to_group()
    {
        $somePhoneNumber = PhoneNumberFixture::validMobile();
        $createdGroup = $this->createGroupWithContact($somePhoneNumber);
        $smsFeature = self::$smsapiService->smsFeature();
        $sendSmsToGroup = SendSmsToGroupBag::withMessage($createdGroup->name, 'some message');
        $sendSmsToGroup->test = true;

        $result = $smsFeature->sendSmsToGroup($sendSmsToGroup);

        $this->assertEquals($somePhoneNumber, $result[0]->number);
        $this->deleteGroup($createdGroup->id);
    }

    /**
     * @test
     */
    public function it_should_send_flash_sms_to_group()
    {
        $somePhoneNumber = PhoneNumberFixture::validMobile();
        $createdGroup = $this->createGroupWithContact($somePhoneNumber);
        $smsFeature = self::$smsapiService->smsFeature();
        $sendFlashSmsToGroup = SendSmsToGroupBag::withMessage($createdGroup->name, 'some message');
        $sendFlashSmsToGroup->test = true;

        $result = $smsFeature->sendFlashSmsToGroup($sendFlashSmsToGroup);

        $this->assertEquals($somePhoneNumber, $result[0]->number);
        $this->deleteGroup($createdGroup->id);
    }

    /**
     * @test
     */
    public function it_should_send_smss()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $receivers = [
            PhoneNumberFixture::validMobile(),
            PhoneNumberFixture::validMobile(),
        ];
        $sendSmsesBag = SendSmssBag::withMessage($receivers, 'some message');
        $sendSmsesBag->test = true;

        $results = $smsFeature->sendSmss($sendSmsesBag);

        $this->assertCount(2, $results);
    }

    /**
     * @test
     */
    public function it_should_send_flash_smss()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $receivers = [
            PhoneNumberFixture::validMobile(),
            PhoneNumberFixture::validMobile(),
        ];
        $sendSmsesBag = SendSmssBag::withMessage($receivers, 'some message');
        $sendSmsesBag->test = true;

        $results = $smsFeature->sendFlashSmss($sendSmsesBag);

        $this->assertCount(2, $results);
    }

    /**
     * @test
     */
    public function it_should_not_receive_details_for_smss()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $receivers = [
            PhoneNumberFixture::validMobile(),
            PhoneNumberFixture::validMobile(),
        ];
        $sendSmsesBag = SendSmssBag::withMessage($receivers, 'some message');
        $sendSmsesBag->test = true;

        $results = $smsFeature->sendSmss($sendSmsesBag);

        foreach ($results as $result) {
            $this->assertNull($result->details);
        }
    }

    /**
     * @test
     */
    public function it_should_schedule_sms()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someDate = new DateTime('+1 day noon');
        $someReceiver = PhoneNumberFixture::validMobile();
        $scheduleSmsBag = ScheduleSmsBag::withMessage($someDate, $someReceiver, 'some message');
        $scheduleSmsBag->test = true;

        $result = $smsFeature->scheduleSms($scheduleSmsBag);

        $this->assertEquals($someDate, $result->dateSent);
        $this->assertEquals($someReceiver, $result->number);
    }

    /**
     * @test
     */
    public function it_should_schedule_smss()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someDate = new DateTime('+1 day noon');
        $receivers = [
            PhoneNumberFixture::validMobile(),
            PhoneNumberFixture::validMobile(),
        ];
        $scheduleSmsBag = ScheduleSmssBag::withMessage($someDate, $receivers, 'some message');
        $scheduleSmsBag->test = true;

        $results = $smsFeature->scheduleSmss($scheduleSmsBag);

        $this->assertCount(2, $results);
    }

    /**
     * @test
     */
    public function it_should_schedule_flash_sms()
    {
        $smsFeature = self::$smsapiService->smsFeature();
        $someDate = new DateTime('+1 day noon');
        $someReceiver = PhoneNumberFixture::validMobile();
        $scheduleFlashSmsBag = ScheduleSmsBag::withMessage($someDate, $someReceiver, 'some message');
        $scheduleFlashSmsBag->test = true;

        $result = $smsFeature->scheduleFlashSms($scheduleFlashSmsBag);

        $this->assertEquals($someDate, $result->dateSent);
        $this->assertEquals($someReceiver, $result->number);
    }

    /**
     * @test
     */
    public function it_should_schedule_sms_to_group()
    {
        $somePhoneNumber = PhoneNumberFixture::validMobile();
        $someDate = new DateTime('+1 day noon');
        $createdGroup = $this->createGroupWithContact($somePhoneNumber);
        $smsFeature = self::$smsapiService->smsFeature();
        $scheduleSmsToGroup = ScheduleSmsToGroupBag::withMessage(
            $someDate,
            $createdGroup->name,
            'some message'
        );
        $scheduleSmsToGroup->test = true;

        $result = $smsFeature->scheduleSmsToGroup($scheduleSmsToGroup);

        $this->assertEquals($someDate, $result[0]->dateSent);
        $this->assertEquals($somePhoneNumber, $result[0]->number);
        $this->deleteGroup($createdGroup->id);
    }

    /**
     * @test
     */
    public function it_should_schedule_flash_sms_to_group()
    {
        $somePhoneNumber = PhoneNumberFixture::validMobile();
        $someDate = new DateTime('+1 day noon');
        $createdGroup = $this->createGroupWithContact($somePhoneNumber);
        $smsFeature = self::$smsapiService->smsFeature();
        $scheduleFlashSmsToGroup = ScheduleSmsToGroupBag::withMessage(
            $someDate,
            $createdGroup->name,
            'some message'
        );
        $scheduleFlashSmsToGroup->test = true;

        $result = $smsFeature->scheduleFlashSmsToGroup($scheduleFlashSmsToGroup);

        $this->assertEquals($someDate, $result[0]->dateSent);
        $this->assertEquals($somePhoneNumber, $result[0]->number);
        $this->deleteGroup($createdGroup->id);
    }

    private function createGroupWithContact(string $phoneNumber): ContactGroup
    {
        $createContactBag = new CreateContactBag();
        $createContactBag->phoneNumber = $phoneNumber;
        $createdContact = self::$smsapiService->contactsFeature()->createContact($createContactBag);
        $createGroupBag = new CreateGroupBag(uniqid('some group '));
        $createdGroup = self::$smsapiService->contactsFeature()->groupsFeature()->createGroup($createGroupBag);
        $assignContactToGroupBag = new AssignContactToGroupBag($createdContact->id, $createdGroup->id);
        self::$smsapiService->contactsFeature()->groupsFeature()->assignContactToGroup($assignContactToGroupBag);

        return $createdGroup;
    }

    private function deleteGroup(string $groupId)
    {
        (new ContactsHelper(self::$smsapiService->contactsFeature()))->deleteGroup($groupId);
    }
}
