# Peakilo: website, creator registration and admin

Plain PHP + MySQL, built for cPanel hosting. No Composer, no Node and no build step: upload the files and run the installer.

## What is included

**Public website**
- **Homepage**
  - Hero with a **niche matchmaker**: a brand picks a niche and a platform, sees how many approved creators match (live from the database), then clicks "Get matched".
  - 3D **creator wall** built from real approved creators. Niche tiles fill the gaps until you have enough creators.
  - GSAP interactions: animated headline, pinned horizontal "five moves" section, image parallax, a marquee that reacts to scroll speed, smooth scrolling.
  - Niche grid, campaign estimator, creator carousel (database only), latest blog posts (database), FAQ with schema markup.
- **Brief builder** (`/contact`): brands choose goal, niches, platforms, creator size, budget and timeline in 4 steps. A live summary shows how many approved creators match. Creators and other visitors get a simple contact form.
- **Influencer registration** (`/join` and campaign links): multi-step form with photo upload, channels with follower counts (accepts `45K`, `1.2M`), niches, content types and a min/max fee range with a live preview card. No password needed.
- **SEO niche landing pages**: `/influencers` plus 20 pages such as `/influencers/beauty`, each with its own copy, creators, campaign ideas and FAQ schema.
- **Blog**: clean URLs, categories, search, table of contents, related posts, share buttons, Article + Breadcrumb schema.
- **Other pages**: About, Services, For brands, For creators, Privacy, Terms, a branded 404 page, `sitemap.xml`, `robots.txt` and an RSS feed at `/feed.xml`.
- Light and dark theme. Respects reduced-motion settings.

**Admin panel** (`/admin`)
- **Dashboard**: registrations and link visits chart, approval queue, platform split, top niches, network reach, average fees, latest messages.
- **Influencers**: card or table view; filters for niche, platform, country, campaign, minimum reach, maximum fee and status. Bulk approve, reject or feature. CSV export. Each influencer has a full profile page with photo, channels, fees, applications, private notes, a star rating and a "feature on website" switch.
- **Campaigns**: each one gets a link like `peakilo.com/c/summer-glow`. You choose what the link does:
  - **Open the form**: shows the registration form with the campaign brief.
  - **Landing page first**: shows the homepage with an "Apply now" banner.
  - **Custom redirect**: sends visitors to any URL.

  Visits, applicants and conversion are tracked per campaign. Applicants move through a pipeline (Applied, Shortlisted, Selected, Rejected) and can be exported to CSV.
- **Blog editor**: rich text, image upload, YouTube embeds, a Google preview, a live SEO score with a checklist, scheduling and categories.
- **Messages**: brand briefs show every brief field, with a "Find matching influencers" button.
- **Subscribers**: newsletter sign-ups with CSV export.
- **Site content**:
  - **Homepage numbers**: shown live from your database by default, or type your own figure to override.
  - **Testimonials**: add, edit, reorder, hide. The section stays hidden until you add one.
  - **Page photos**: upload your own photo for each of the 8 photo spots on the site.
- **Settings**: site details, contact info, social links, Google Analytics, Search Console verification, your password and team admins.

## Run it locally (XAMPP)

`config.local.php` switches the site to a SQLite file so you do not need MySQL running.

```bash
cd D:\Influencer
D:\xampp\php\php.exe -S 127.0.0.1:8090 index.php
```

Open http://127.0.0.1:8090. The local test admin login is written in `config.local.php`.

The local SQLite database contains **test influencers** (emails ending in `@example.test`, source `local-test`) so you can see the creator wall and admin screens filled. They exist only in `storage/database.sqlite` and are never uploaded.

To test against XAMPP MySQL instead, delete `config.local.php`, put your local MySQL details in `config.php`, then visit `/install`.

## Deploy to cPanel

1. **Create the database.** In cPanel > MySQL Databases, create a database and a user, then add the user to the database with ALL PRIVILEGES.
2. **Set the PHP version.** In cPanel > MultiPHP Manager, choose PHP 8.0 or newer (7.4 also works). Under Select PHP Version > Extensions, enable `pdo_mysql`, `fileinfo` and `gd`. GD resizes photos and converts them to WebP.
3. **Upload the files** to `public_html`, using File Manager (upload a zip and extract it) or FTP. **Do not upload:**
   - `config.local.php`
   - `storage/database.sqlite` (and any `storage/database.sqlite-*` files)
   - `storage/installed.lock`
   - the `.claude` folder
4. **Edit `config.php`** on the server: set `site_url` to `https://peakilo.com` and fill in the `db` block with the name, user and password from step 1.
5. **Folder permissions:** `uploads/` and `storage/` must be writable (755 usually works; use 775 if uploads fail).
6. **Run the installer:** visit `https://peakilo.com/install` and create your admin account. It creates the tables, three starter blog posts and an "Open Call" campaign, then locks itself.
7. **Turn on HTTPS:** once SSL is active (cPanel > SSL/TLS Status, AutoSSL), uncomment the HTTPS lines at the top of `.htaccess`. Optionally uncomment the www redirect too.
8. **Connect Google:** in Admin > Settings, add your GA4 ID and Search Console verification code, then submit `https://peakilo.com/sitemap.xml` in Search Console.

Future database changes are applied automatically through versioned migrations in `app/schema.php`.

## Before you launch

- **Testimonials:** add real quotes (with permission) in Admin > Site content. The section is hidden until you do.
- **Homepage numbers:** these use live figures by default:
  - approved creators
  - closed campaigns
  - combined followers of approved creators

  Any figure that is zero is hidden. In Admin > Site content you can type your own numbers (for example campaigns you ran before this website) and add a repeat brand rate.
- **Photos:** the 8 photo spots start with stock placeholders. Upload your own in Admin > Site content > Page photos.
- **Starter blog posts** use stock cover images. Change them in Admin > Blog posts, or delete the posts.
- **Logo:** the chevron mark is recreated as an SVG in `mark_svg()` in `app/helpers.php` and in `assets/img/logo-mark.svg`. If your designer has the official SVG, paste its path there.
- **Social preview and icons:** these are ready:
  - `assets/img/og-default.png` (1200 x 630)
  - `favicon.ico`
  - the app icons
  - `site.webmanifest`

  Replace the PNGs if your designer makes their own versions.
- **Legal pages:** have the Privacy and Terms pages reviewed for your country.
- **Featured creators:** an influencer only appears on the public site when all three are true: their status is **Approved**, they ticked **"Peakilo may feature my profile"** and they uploaded a photo. Use **Feature** to put someone first.
- **No passwords for creators:** submitting the form again with the same email updates that profile, and a note is added to the admin profile.

## Security built in

- PDO prepared statements everywhere.
- CSRF tokens on every form.
- Hashed admin passwords and login throttling.
- Uploads are checked by their contents, renamed randomly and stored where scripts cannot run.
- Private folders are blocked by `.htaccess`.
- Visitor IPs are stored only as one-way hashes.
- CSV exports are protected against spreadsheet formula injection.
- Honeypot spam traps and rate limits on public forms.

## Where things live

```
index.php              routes
config.php             production settings (edit on the server)
app/controllers/       site, blog + SEO files, join (registration + campaign links), admin, install
app/views/             templates (site, blog, join, admin, partials)
app/niches.php         niche catalogue, copy for niche pages, live counts
app/schema.php         database tables, starter content, migrations
assets/css/            site.css, motion.css (hero, GSAP sections), join.css, admin.css
assets/js/             site.js, motion.js (GSAP), home.js (hero), brief.js, join.js, admin.js, editor.js
uploads/               creator photos, blog and campaign images
storage/               install lock (and the local SQLite file)
```
