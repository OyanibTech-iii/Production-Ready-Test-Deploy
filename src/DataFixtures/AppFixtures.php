<?php

namespace App\DataFixtures;

use App\Entity\BotKnowledge;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $knowledgeEntries = [
            'when growfico was founded' => 'Growfico was founded on August 15, 2025.',
            'marcot tree' => 'Marcot tree is a tree that produces fruit in a short period of time.',
            'grafted plant' => 'Grafted plant is a plant that has been grafted to another plant to produce a new plant.',
            'growfico official' => 'Is the official brand of Growfico.',
            'growfico' => 'Growfico is an agricultural and sustainability platform that connects people with practical tools, products, and services for greener living.',
            'i need help' => 'I can help you with services, locations, courses, contact details, and account questions.',
            'i need assistance' => 'I can help you with services, locations, courses, contact details, and account questions.',
            'hello' => 'Hi there! Welcome to Growfico support. How can I help you today?',
            'hi' => 'Hello! I can help with services, locations, courses, contact details, and account questions.',
            'who are you' => 'I am ficoBot, the Growfico customer support assistant.',
            'how to use ficoBot' => 'You can use ficoBot by asking questions about Growfico services, locations, courses, contact details, and account questions.',
            'how to contact support' => 'You can contact support by using the chat assistant or contact us via email/phone on the Contact section.',
            'what is your name' => 'I am ficoBot.',
            'what is growfico' => 'Growfico is an agricultural and sustainability platform that connects people with practical tools, products, and services for greener living.',
            'mission' => 'Growfico\'s mission is to make sustainable agriculture more accessible through quality planting solutions, landscaping support, organic inputs, and education.',
            'vision' => 'Growfico focuses on cultivating greener communities and making sustainable agriculture practical for everyone.',
            'founder' => 'Mr. Pacifico M. Oyanib III is the founder of Growfico.',
            'how to create a new account' => 'You can create a new account using the REGISTER button on the landing page.',
            'founded date' => 'Growfico was founded on August 15, 2025.',
            'how to login' => 'You can login using the LOGIN button on the landing page.',
            'after login what next' => 'After login, go to your dashboard/account page, review available services or courses, and proceed with the action you need (inquiry, booking, or enrollment).',
            'after i login what should i do' => 'After logging in, open your account/dashboard and choose your next step: browse services, check courses, or contact support for guidance.',
            'what is the purpose of the app' => 'The purpose of the app is to provide a platform for users to access Growfico services, locations, courses, contact details, and account questions.',
            'what is the currency of the app' => 'The currency of the app is the Philippine Peso (PHP).',
            'what is the language of the app' => 'The language of the app is English.',
            'developer' => 'The developer of the app is Mr. Pacifico M. Oyanib III.',
            'what grade i want' => 'Flat 1 hahaha ',
            'open hours' => 'Our customer support and regular office hours are Monday to Friday, 9:00 AM to 6:00 PM.',
            'office address' => 'Our office is located at Kagawasan Avenue, Dumaguete City, 6200 Negros Oriental.',
            'location' => 'You can find us at our main branch in Dumaguete City.',
            'after register what next' => 'After registration, log in with your new account, complete your profile if needed, then explore services, products, and courses.',
            'after i register what should i do' => 'After registering, sign in, verify your details, and continue to the section you need such as courses, services, or support chat.',
            'is ficoBot a robot' => 'Yes, ficoBot is a robot.',
            'what is ficoBot made of' => 'ficoBot is made of PHP, Symfony, Doctrine ORM, Twig, JavaScript, Tailwind CSS, and Webpack Encore.',
            'branches' => 'Growfico has branches in Dumaguete, Bayawan, Pamplona, Bacolod, and Sipalay.',
            'email address' => 'You can contact us at growficoofficial@gmail.com.',
            'phone number' => 'You can call us at +63 945 806 2493.',
            'website' => 'Our official website is www.growfico.com but it is not yet available.',
            'facebook' => 'Our Facebook page is www.facebook.com/GROWFICO_Official.',
            'instagram' => 'Our Instagram is www.instagram.com/GROWFICO_Official.',
            'services' => 'Our core services include premium plant materials, professional landscaping, agricultural inputs, and education/training.',
            'premium plant materials' => 'We offer marcotted fruiting trees, grafted plants, and disease-resistant varieties for better and healthier yields.',
            'marcot harvest' => 'For marcotted trees, harvesting can usually start around 1 to 2 months after establishment, while stronger and more consistent fruit-bearing is commonly around 3 to 4 years.',
            'marcot havest' => 'For marcotted trees, harvesting can usually start around 1 to 2 months after establishment, while stronger and more consistent fruit-bearing is commonly around 3 to 4 years.',
            'marcotted fruit timeline' => 'Marcotted trees may show early harvest potential in about 1 to 2 months, with fuller fruit-bearing performance typically around 3 to 4 years.',
            'landscaping' => 'We provide residential and commercial landscaping: design, installation, hardscape/irrigation integration, and maintenance.',
            'agricultural inputs' => 'We provide organic fertilizers, compost products, bio-based nutrient solutions, and soil amendments.',
            'education and training' => 'We provide organic farming training sessions, workshops, seminars, and certification courses.',
            'courses' => 'Growfico offers training programs and educational content, including beginner-to-advanced gardening and organic farming courses.',
            'certification courses' => 'Yes, Growfico provides certification courses as part of its education and training services.',
            'workshops' => 'Yes, we conduct workshops and seminars focused on practical agriculture and sustainability.',
            'do you offer beginner courses' => 'Yes. Growfico supports beginner-to-advanced learners with practical gardening and farming education.',
            'how to register' => 'You can register using the REGISTER button on the landing page.',
            'account deactivated' => 'If your account is deactivated, please contact Growfico support through growficoofficial@gmail.com or +63 945 806 2493 for reactivation assistance.',
            'who to contact if account is deactivated' => 'Please contact Growfico support via email at growficoofficial@gmail.com or phone at +63 945 806 2493.',
            'forgot password' => 'If you forgot your password, please use the password reset flow from the login page if available, or contact support for help.',
            'chat support' => 'You can open ficoBot from the floating bot icon on the landing page and send your question.',
            'clear chat' => 'Use the trash icon in the chat window to clear your current conversation.',
            'is my account secure' => 'Growfico uses secure authentication and account management for user access.',
            'supported devices' => 'Growfico is designed with a responsive interface for both desktop and mobile devices.',
            'technology stack' => 'The app is built with Symfony, PHP, Doctrine ORM, Twig, JavaScript, Tailwind CSS, and Webpack Encore.',
            'test command' => 'Developers can run tests using php bin/phpunit.',
            'how to logout' => 'You can logout using the LOGOUT button on the account page.',
            'how to reset password' => 'You can reset your password using the FORGOT PASSWORD button on the login page. under development',
            'how to change password' => 'You can change your password using the CHANGE PASSWORD button on the account page. under development',
            'how to delete account' => 'You can delete your account using the DELETE ACCOUNT button on the account page.under development',
            'how to change email' => 'You can change your email using the CHANGE EMAIL button on the account page. under development',
            'how to change phone' => 'You can change your phone using the CHANGE PHONE button on the account page. under development',
            'how to run app' => 'Typical setup: install dependencies, configure .env.local, run migrations, then start Symfony server and frontend watcher.',
            'where is support' => 'For customer support, please use this chat assistant or contact us via email/phone on the Contact section.',
            'product inquiry' => 'For product inquiries, please share what you need (plants, inputs, or landscaping) and we will guide you.',
            'pricing' => 'Pricing depends on the specific product, service, or course tier. Please tell us what you are interested in for a tailored quote.',
            'book landscaping' => 'To book landscaping services, share your location and project details, and our team will assist you with next steps.',
            'organic fertilizer' => 'Yes, we offer organic fertilizers and compost-based solutions for sustainable growing.',
            'irrigation support' => 'Yes, our landscaping services include hardscape and irrigation integration.',
            'community support' => 'Growfico supports communities by promoting accessible, practical, and sustainable agriculture education.',
            'thank you' => 'You are welcome! If you need anything else, I am here to help.',
            'bye' => 'Thanks for reaching out to Growfico. Have a great day!',
        ];

        $sensitivePatterns = [
            '/api[_-]?key/i',
            '/access[_-]?token/i',
            '/secret/i',
            '/bearer\\s+[a-z0-9\\-\\._~\\+\\/]+=*/i',
            '/password/i',
            '/client[_-]?secret/i',
            '/private[_-]?key/i',
            '/\\bsk_[a-z0-9]{10,}\\b/i',
            '/\\bghp_[a-z0-9]{20,}\\b/i',
            '/\\b[A-Za-z0-9_\\-]{32,}\\b/',
        ];

        foreach ($knowledgeEntries as $keyword => $answer) {
            if ($this->containsSensitiveContent($keyword, $answer, $sensitivePatterns)) {
                continue;
            }

            $normalizedKeyword = strtolower(trim($keyword));
            $existing = $manager->getRepository(BotKnowledge::class)->findOneBy(['keyword' => $normalizedKeyword]);

            $knowledge = $existing ?? new BotKnowledge();
            $knowledge->setKeyword($normalizedKeyword);
            $knowledge->setAnswer(trim($answer));
            $manager->persist($knowledge);
        }

        $manager->flush();
    }

    /**
     * Prevent accidental seeding of sensitive values.
     */
    private function containsSensitiveContent(string $keyword, string $answer, array $patterns): bool
    {
        $combined = $keyword . ' ' . $answer;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $combined) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function getGroups(): array
    {
        return ['ficoBot'];
    }
}