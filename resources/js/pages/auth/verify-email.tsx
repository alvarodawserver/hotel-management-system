// Components
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle, MailCheck } from 'lucide-react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Email verification')} />

            <div className="mb-6 flex justify-center">
                <span className="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <MailCheck className="size-6" />
                </span>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {t(
                        'A new verification link has been sent to the email address you provided during registration.',
                    )}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            {t('Resend verification email')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('Log out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify your email',
    description:
        'We have sent you a link to confirm your email address. Open it to start booking.',
};
