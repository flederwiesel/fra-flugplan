#!/bin/bash

# https://www.sslshopper.com/article-most-common-openssl-commands.html
# https://spin.atomicobject.com/2014/05/12/openssl-commands/
# http://www.shellhacks.com/en/HowTo-Create-CSR-using-OpenSSL-Without-Prompt-Non-Interactive

# http://superuser.com/questions/904564/utf8-and-t61-strings-how-do-i-see-what-my-ssl-cert-uses#904565

# http://www.akadia.com/services/ssh_test_certificate.html
# http://serverfault.com/questions/9708/what-is-a-pem-file-and-how-does-it-differ-from-other-openssl-generated-key-file#9717
# https://de.wikipedia.org/wiki/Public-Key_Cryptography_Standards
#
# PEM: The name is from Privacy Enhanced Mail (PEM), a failed method for
#      secure email but the container format it used lives on, and is a base64
#      translation of the x509 ASN.1 keys.
# CSR: PKCS#10 X.509 Certificate Signing Request
# PKCS: Public-Key Cryptography Standards

export domain=fra-flugplan.de
export passwd='s/p4sswd/***/SSL@fra-flugplan.de'
export subj='/C=DE/L=Ludwigshafen/O=flederwiesel/OU=FRA Flugplan/CN=Tobias K\\xc3\\xbchne/emailAddress=postmaster@fra-flugplan.com'

mkdir -p private
mkdir -p certs
mkdir -p csr

if false; then
#  Generate a Private Key
openssl genpkey \
    -algorithm RSA \
    -pkeyopt rsa_keygen_bits:4096 \
    -aes-256-cbc \
    -out private/$domain.key.enc \
    -pass env:passwd

# Remove Passphrase from Key
openssl rsa \
    -out private/$domain.key \
    -in private/$domain.key.enc \
    -passin env:passwd

################################################################################
# !!!IMPORTANT !!!
chmod 0600 private/$domain.key
chown root:root private/$domain.key
chown root:root private/$domain.key.enc
# !!!IMPORTANT !!!
################################################################################
fi

# Generate a Certificate Signing Request
openssl req \
    -new \
    -utf8 \
    -out csr/$domain.csr \
    -key private/$domain-$(date +'%Y-%m-%d_%H%M%S').key \
    -subj "$subj"
