<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\CoreBundle\Tests\Mailer;

use Sylius\Bundle\CoreBundle\Mailer\ResetPasswordEmailManagerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Test\SwiftmailerAssertionTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResetPasswordEmailManagerTest extends KernelTestCase
{
    use SwiftmailerAssertionTrait;

    private const RECIPIENT_EMAIL = 'sylius@example.com';

    private ResetPasswordEmailManagerInterface $resetPasswordEmailManager;

    private TranslatorInterface $translator;

    private AdminUser $adminUser;

    protected function setUp(): void
    {
        $this->resetPasswordEmailManager = self::getContainer()->get(ResetPasswordEmailManagerInterface::class);

        $this->translator = self::getContainer()->get('translator');

        $this->adminUser = new AdminUser();
        $this->adminUser->setEmail(self::RECIPIENT_EMAIL);
    }

    /** @test */
    public function it_sends_admin_reset_password_email(): void
    {
        if (self::isItSwiftmailerTestEnv()) {
            $this->markTestSkipped('Test is relevant only for the environment without swiftmailer');
        }

        $this->resetPasswordEmailManager->sendAdminResetPasswordEmail($this->adminUser, 'en_US');

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'To', self::RECIPIENT_EMAIL);
        self::assertEmailHtmlBodyContains(
            $email,
            $this->translator->trans(id: 'sylius.email.admin_password_reset.to_reset_your_password_token', locale: 'en_US'),
        );
    }

    /** @test */
    public function it_sends_reset_password_email_with_swiftmailer(): void
    {
        if (!self::isItSwiftmailerTestEnv()) {
            $this->markTestSkipped('Test is relevant only for the environment with swiftmailer');
        }

        $container = self::getContainer();

        self::setSpoolDirectory($container->getParameter('kernel.cache_dir') . '/spool');

        /** @var Filesystem $filesystem */
        $filesystem = $container->get('filesystem');
        $filesystem->remove(self::getSpoolDirectory());

        $this->resetPasswordEmailManager->sendAdminResetPasswordEmail($this->adminUser, 'en_US');

        self::assertSpooledMessagesCountWithRecipient(1, self::RECIPIENT_EMAIL);
        self::assertSpooledMessageWithContentHasRecipient(
            $this->translator->trans('sylius.email.admin_password_reset.to_reset_your_password_token', [], null, 'en_US'),
            self::RECIPIENT_EMAIL,
        );
    }
}
