# Project "ENI - Sortir"

## Team

Camille - Emilien - Laurine - Vanessa

## Description

The Sortir.com project aims to create a web platform allowing current and former ENI trainees to plan and participate in extracurricular outings.

## Prerequisites / Installation

A text editor or IDE (e.g., PHPStorm)

### Web development platform

Install the last Wampserver full install version from [https://wampserver.aviatechno.net/].
And download all VC Redistributable Packages (x86_x64) (32 & 64bits) and install the necessary ones.

### Symfony Framework

Download [https://symfony.com/download] : Binaries from GitHub: 386 or amd64.
And add the execution file to the wampServer bin.

### Dependancy Manager Composer

Download and run Composer-Setup.exe from [https://getcomposer.org/download/]

### Mailing

Download and execute your version on [https://github.com/mailhog/MailHog/releases]

Add in env.dev file : MAILER_DSN=smtp://localhost:**** (look at your terminal  (e.g.,  [SMTP] Binding to address: 0.0.0.0:1025))

Modify messenger.yaml :
 - 'sync://' ==>> 'sync'
 - Symfony\Component\Mailer\Messenger\SendEmailMessage: async ==>> Symfony\Component\Mailer\Messenger\SendEmailMessage: sync

### Database

The project uses a My SQL Database
| Name  | User          | Password | Port |
| :--------------- |:---------------:| -----:| -----:|
| eni-sortir  |   root       |  none | 3306

### Api

APi documentation is available from the application on the **api/doc** route.

### Particular dependencies

- device detector : acsiomatic
- api doc : nelmio
- templating : twig
