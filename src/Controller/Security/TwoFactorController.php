<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Entity\User\User;
use App\Form\Security\TwoFactorSetupType;
use App\Repository\User\UserRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TwoFactorController extends AbstractController
{
    #[Route('/admin/security/2fa', name: 'admin_security_2fa')]
    public function setup(
        Request $request,
        GoogleAuthenticatorInterface $googleAuthenticator,
        UserRepository $userRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($user->isGoogleAuthenticatorEnabled()) {
            $this->addFlash(
                'info',
                'La double authentification est déjà activée.'
            );

            return $this->redirectToRoute('home');
        }

        $session = $request->getSession();
        $secret = $session->get('2fa_setup_secret');

        if (!is_string($secret) || '' === $secret) {
            /** @var non-empty-string $secret */
            $secret = $googleAuthenticator->generateSecret();

            $session->set('2fa_setup_secret', $secret);
        }

        /*
         * Le secret est temporairement placé sur l'utilisateur
         * uniquement pour permettre à Scheb de générer le
         * provisioning URI utilisé par le QR code.
         */
        $user->setGoogleAuthenticatorSecret($secret);

        $qrContent = $googleAuthenticator->getQRContent($user);

        /*
         * Le secret n'est pas encore activé.
         * Il reste uniquement en session jusqu'à validation
         * du premier code TOTP.
         */
        $user->setGoogleAuthenticatorSecret(null);

        $qrCode = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $qrDataUri = $qrCode->build()->getDataUri();

        $form = $this->createForm(TwoFactorSetupType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{code: string} $data */
            $data = $form->getData();

            $code = $data['code'];

            if ('' === $code) {
                throw new \LogicException('Le code 2FA ne peut pas être vide.');
            }

            $totp = TOTP::createFromSecret($secret)
                ->withDigits(6)
                ->withPeriod(30)
                ->withDigest('sha1');

            /*
             * 15 secondes de leeway pendant l'enrollment.
             *
             * Cela permet notamment d'éviter qu'un code valide
             * soit rejeté si l'utilisateur le saisit exactement
             * au changement de fenêtre TOTP.
             */
            if (!$totp->verify($data['code'], null, 15)) {
                $form->get('code')->addError(
                    new FormError(
                        'Le code de vérification est invalide.'
                    )
                );
            } else {
                /*
                 * Le code est valide :
                 * le secret peut maintenant être persisté.
                 */
                $user->setGoogleAuthenticatorSecret($secret);

                $userRepository->save($user, true);

                $session->remove('2fa_setup_secret');

                $this->addFlash(
                    'success',
                    'La double authentification a été activée.'
                );

                return $this->redirectToRoute('home');
            }
        }

        return $this->render('security/2fa_setup.html.twig', [
            'form' => $form,
            'qrDataUri' => $qrDataUri,
            'secret' => $secret,
        ]);
    }
}
