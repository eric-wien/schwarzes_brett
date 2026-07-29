# Publishing to the Nextcloud App Store

The app id is `schwarzes_brett`. Keep it unchanged: the certificate, archive
folder, App Store registration, and installed app directory all use this id.

Never commit the private key or copy it into an issue or pull request.

## One-time setup

1. Sign in to [apps.nextcloud.com](https://apps.nextcloud.com/) with the GitHub
   account that will own the app. Make sure the GitHub profile exposes a contact
   email address while the certificate request is reviewed.

2. Create the private key and certificate signing request:

   ```sh
   mkdir -p ~/.nextcloud/certificates
   chmod 700 ~/.nextcloud/certificates
   openssl req -nodes -newkey rsa:4096 \
     -keyout ~/.nextcloud/certificates/schwarzes_brett.key \
     -out ~/.nextcloud/certificates/schwarzes_brett.csr \
     -subj "/CN=schwarzes_brett"
   chmod 600 ~/.nextcloud/certificates/schwarzes_brett.key
   ```

3. In
   [nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests),
   create `schwarzes_brett/schwarzes_brett.csr`, paste only the CSR, link to
   this repository, and open a pull request.

4. After approval, save the returned public certificate as
   `~/.nextcloud/certificates/schwarzes_brett.crt`.

5. Register the app at
   [apps.nextcloud.com/developer/apps/new](https://apps.nextcloud.com/developer/apps/new).
   Paste the public certificate and this app-id signature:

   ```sh
   printf %s schwarzes_brett |
     openssl dgst -sha512 \
       -sign ~/.nextcloud/certificates/schwarzes_brett.key |
     openssl base64 -A
   ```

6. Create a protected GitHub environment named `release`, require a trusted
   reviewer, and add these environment secrets:

   - `APP_PRIVATE_KEY`: the complete contents of `schwarzes_brett.key`
   - `APPSTORE_TOKEN`: the token from the Nextcloud App Store account settings

## Publish a release

1. Update `appinfo/info.xml` and `CHANGELOG.md` to the same semantic version.
2. Run `make verify-release` and `make appstore`.
3. Commit and push the release changes.
4. Create and publish a GitHub release with tag `v<version>`, for example
   `v1.3.1`.
5. Approve the `release` environment deployment.

The workflow builds an archive with one `schwarzes_brett/` top-level directory,
attaches it to the GitHub release, signs the archive with SHA-512, and submits
the HTTPS download URL and signature to the App Store REST API. GitHub
pre-releases must use a semantic-version suffix such as `-beta.1`; the App Store
then puts them on its beta channel. The workflow does not publish nightlies.

The local archive is written to `build/artifacts/appstore/`.
