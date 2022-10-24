Secret Santa
Symfony 6.1, PHP 8.1, SQLite simple project.
Allows adding people to the draw.
Use with Warden under `secret-santa.local/admin`

To draw and send emails run: `bin/console app:draw`

Set up of MAILER_DSN environment variable is necessary, default includes Warden Mailhog configuration only.