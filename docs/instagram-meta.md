# Instagram stats via Meta's Business Discovery API

The creator form fills in **followers, average Reel views and engagement rate**
for Instagram **business and creator** accounts using Meta's official Graph API
(Business Discovery). Creators do not log in. Personal accounts and age-gated
accounts are not covered, and **impressions/reach are never available** for other
people's accounts (only the owner sees them). The lookup service in
`services/social-metrics` stays as the fallback.

What Peakilo needs: its own Instagram professional account linked to a Facebook
Page, a free Meta developer app, the Instagram account ID and an access token.

## A. Instagram and Facebook (about 10 minutes)

1. **Make @peakilo.media professional.** Instagram app > your profile > menu >
   *Settings and activity* > *Account type and tools* > *Switch to professional
   account* > choose **Business** (or Creator) and a category.
2. **Have a Facebook Page for Peakilo.** If there is none, create one at
   facebook.com/pages/create.
3. **Link them.** Instagram app > *Edit profile* > *Page* > *Connect* and pick the
   Peakilo Page. (Or Meta Business Suite > *Settings* > *Accounts* > *Instagram
   accounts* > *Connect*.)

## B. Meta developer app (about 5 minutes)

4. Go to **developers.facebook.com**, log in with the Facebook account that
   manages the Page and finish *Get started* (phone verification if asked).
5. *My Apps* > **Create app**. Name: `Peakilo Lookup`. Use case: **Other**. App
   type: **Business**. Connect Peakilo's business portfolio if offered.
6. In the app dashboard: *Add product* > **Instagram** > **API setup with
   Facebook login**.

## C. Account ID and token (about 5 minutes)

7. Open **Tools > Graph API Explorer**. Pick the `Peakilo Lookup` app, choose
   *Get User Access Token* and tick these permissions:
   `instagram_basic`, `pages_show_list`, `pages_read_engagement`,
   `business_management`. Click **Generate Access Token** and, in the pop-up,
   select the Peakilo Page and @peakilo.media.
8. In the query box run:
   `me/accounts?fields=name,instagram_business_account`
   Copy `instagram_business_account` > `id` (a long number like
   `17841400000000000`). This is the **Instagram business account ID**.
9. Test it (replace the ID):
   `17841400000000000?fields=business_discovery.username(chennaiipl){followers_count,media_count}`
   You should see the follower count.
10. **Make a token that does not expire (recommended).** business.facebook.com >
    *Settings* > *Users* > **System users** > *Add* (name `peakilo-lookup`, role
    Admin) > *Assign assets*: the Peakilo Page, the Instagram account and the
    `Peakilo Lookup` app > **Generate new token** > choose the app, expiration
    **Never**, the same four permissions > copy the token.
    *Quick alternative:* paste the Explorer token into the Access Token Debugger
    (developers.facebook.com/tools/debug/accesstoken) and click *Extend Access
    Token* (lasts 60 days, then repeat).

## D. Connect Peakilo

11. peakilo.com/admin > **Settings** > **Instagram (Meta API)**: paste the
    account ID and the token, then **Save settings**. The section says
    *Connected.* The token is never shown again; paste a new one to replace it.
12. Test on peakilo.com/join: turn on Instagram, paste a business or creator
    profile and watch followers, average views and engagement fill in.

## Notes

- The app can stay in **Development mode**: the token belongs to an app admin.
  If Meta ever requires App Review / Advanced Access for these permissions, the
  lookup quietly falls back and creators type their numbers in.
- Average views = mean of Instagram's public `view_count` (paid + organic) on the
  latest 12 Reels. Engagement = average likes + comments on the latest 12 posts
  divided by followers.
- An expired token or a Meta rate limit makes the lookup fall back to the
  lookup service; nothing breaks for creators.
- Never put the token in Git, in `config.php` or in a support message.
