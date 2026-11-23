import { chromium } from 'playwright-core';

const base = process.env.FRONTEND_BASE_URL ?? 'http://127.0.0.1:8000';
const stamp = Date.now().toString().slice(-7);
const browser = await chromium.launch({
    executablePath: process.env.CHROME_PATH ?? '/usr/bin/google-chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const errors = [];
let currentPage = null;

async function newPage(context) {
    const page = await context.newPage();
    page.setDefaultTimeout(15_000);
    page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
        if (message.type() === 'error' && !message.text().includes('favicon')) {
            errors.push(`console: ${message.text()}`);
        }
    });
    page.on('response', (response) => {
        if (response.status() >= 400) {
            errors.push(`http ${response.status()}: ${response.url()}`);
        }
    });
    currentPage = page;
    return page;
}

async function verifyOtp(page) {
    await page.getByText('Enter your verification code').waitFor();
    const digits = ['1', '2', '3', '4'];
    for (let index = 0; index < digits.length; index += 1) {
        await page.getByLabel(`Digit ${index + 1}`).fill(digits[index]);
    }
    await page.getByRole('button', { name: 'Verify and continue' }).click();
}

try {
    const theaterContext = await browser.newContext();
    const theater = await newPage(theaterContext);

    await theater.goto(base, { waitUntil: 'networkidle' });
    await theater.getByText('One stage for every theater, group, and audience.').waitFor();

    await theater.goto(`${base}/register/theater`, { waitUntil: 'networkidle' });
    await theater.getByLabel('Theater name').fill(`Smoke Theater ${stamp}`);
    await theater.getByLabel('Workspace address').fill(`smoke-theater-${stamp}`);
    await theater.getByLabel('Owner name').fill('Smoke Owner');
    await theater.getByLabel('Email address').fill(`owner-${stamp}@smoke.test`);
    await theater.getByLabel('Phone number').fill('500000001');
    await theater.getByLabel('Password', { exact: true }).fill('P@ssw0rd123');
    await theater.getByLabel('Confirm password').fill('P@ssw0rd123');
    await theater.getByRole('button', { name: 'Create workspace' }).click();
    await verifyOtp(theater);
    await theater.waitForURL(`${base}/theater`);
    await theater.getByText('Theater dashboard').waitFor();
    console.log('✓ theater registration and dashboard');

    await theater.goto(`${base}/theater/team`, { waitUntil: 'networkidle' });
    await theater.getByText('Team', { exact: true }).first().waitFor();
    await theater.getByText('Owner').first().waitFor();
    console.log('✓ theater team page');

    await theater.goto(`${base}/theater/roles`, { waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Create role' }).click();
    await theater.getByLabel('Role name').fill('Smoke Coordinator');
    await theater.getByText('Permissions', { exact: true }).first().waitFor();
    const firstPermission = theater.locator('input[type="checkbox"]').first();
    await firstPermission.check();
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Role created.').waitFor();
    console.log('✓ tenant role create with permission catalog');

    await theater.goto(`${base}/theater/invitations`, { waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Invite member' }).first().click();
    await theater.getByLabel('Email address').fill(`invitee-${stamp}@smoke.test`);
    await theater.getByLabel('Name').fill('Invited Member');
    await theater.getByRole('button', { name: 'Invite member' }).last().click();
    await theater.getByText('Invitation sent.').waitFor();

    const tenantToken = await theater.evaluate(() => {
        const raw = window.sessionStorage.getItem('theaterlink.session');
        return raw ? JSON.parse(raw).token : null;
    });
    const invitationToken = await theater.evaluate(async (token) => {
        const response = await fetch('/api/v1/tenant/team/invitations', {
            headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const payload = await response.json();
        return payload.data[0]?.token ?? null;
    }, tenantToken);

    const inviteContext = await browser.newContext();
    const invitePage = await newPage(inviteContext);
    await invitePage.goto(`${base}/invitations/accept?token=${encodeURIComponent(invitationToken ?? '')}`, { waitUntil: 'networkidle' });
    await invitePage.getByLabel('Name').fill('Invited Member');
    await invitePage.getByLabel('Password', { exact: true }).fill('P@ssw0rd123');
    await invitePage.getByLabel('Confirm password').fill('P@ssw0rd123');
    await invitePage.getByRole('button', { name: 'Accept invitation' }).click();
    await invitePage.waitForURL(`${base}/theater`);
    await invitePage.getByText('Theater dashboard').waitFor();
    console.log('✓ invitation create and acceptance');

    await theater.goto(`${base}/theater/halls`, { waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Add hall' }).first().click();
    await theater.getByLabel('Hall name').fill(`Smoke Hall ${stamp}`);
    await theater.getByLabel('Capacity').fill('80');
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Hall created.').waitFor();
    currentPage = theater;
    await theater.getByRole('link', { name: 'Open hall' }).first().click();
    await theater.waitForURL(/\/theater\/halls\/\d+/);
    await theater.getByText('Weekly schedules').waitFor();
    await theater.getByRole('button', { name: 'Add schedule' }).click();
    await theater.getByLabel('Day').selectOption('1');
    await theater.getByLabel('Start time').fill('10:00');
    await theater.getByLabel('End time').fill('12:00');
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Schedule created.').waitFor();
    console.log('✓ theater hall and weekly schedule');

    await theater.goto(`${base}/theater/events`, { waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Create event' }).first().click();
    await theater.getByLabel('English title').fill(`Smoke Event ${stamp}`);
    await theater.getByLabel('Arabic title').fill(`حدث ${stamp}`);
    await theater.getByLabel('English description').fill('A smoke-test performance.');
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Event created.').waitFor();
    await theater.getByRole('link', { name: 'Open event' }).first().click();
    const eventUrl = theater.url();
    await theater.getByRole('button', { name: 'Add show' }).click();
    await theater.getByLabel('Hall').selectOption({ index: 1 });
    await theater.getByLabel('Starts at').fill('2026-12-10T18:00');
    await theater.getByLabel('Ends at').fill('2026-12-10T20:00');
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Show created.').waitFor();
    await theater.getByRole('link', { name: 'Ticket tiers' }).click();
    await theater.getByRole('button', { name: 'Add ticket tier' }).first().click();
    await theater.getByLabel('English name').fill('General Admission');
    await theater.getByLabel('Price').fill('100');
    await theater.getByLabel('Total quantity').fill('50');
    await theater.getByRole('button', { name: 'Save' }).click();
    await theater.getByText('Ticket tier created.').waitFor();
    await theater.goto(eventUrl, { waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Publish' }).click();
    await theater.getByRole('button', { name: 'Confirm' }).last().click();
    await theater.getByText('Event published.').waitFor();
    const publicHref = await theater.getByRole('link', { name: 'View public page' }).getAttribute('href');
    if (!publicHref) throw new Error('Public event link was not rendered.');
    await theater.goto(`${base}${publicHref}`, { waitUntil: 'networkidle' });
    await theater.getByText(`Smoke Event ${stamp}`).waitFor();
    console.log('✓ event, show, ticket tier, publish, and public catalog');

    await theater.goto(`${base}/admin/login`, { waitUntil: 'networkidle' });
    await theater.getByLabel('Email address').fill('admin@base.com');
    await theater.getByLabel('Password', { exact: true }).fill('P@ssw0rd');
    await theater.getByRole('button', { name: 'Sign in' }).click();
    await theater.waitForURL(`${base}/admin`);
    await theater.getByText('Platform overview').waitFor();
    console.log('✓ admin login and dashboard');

    const resetContext = await browser.newContext();
    const reset = await newPage(resetContext);
    await reset.goto(`${base}/forgot-password/theater`, { waitUntil: 'networkidle' });
    await reset.getByLabel('Workspace address').fill(`smoke-theater-${stamp}`);
    await reset.getByLabel('Email address').fill(`owner-${stamp}@smoke.test`);
    await reset.getByRole('button', { name: 'Send reset code' }).click();
    for (let index = 0; index < 4; index += 1) {
        await reset.getByLabel(`Digit ${index + 1}`).fill(['1', '2', '3', '4'][index]);
    }
    await reset.getByRole('button', { name: 'Verify code' }).click();
    await reset.getByLabel('New password', { exact: true }).fill('NewP@ssw0rd123');
    await reset.getByLabel('Confirm new password').fill('NewP@ssw0rd123');
    await reset.getByRole('button', { name: 'Set new password' }).click();
    await reset.getByText('Your password has been reset. You can sign in now.').waitFor();
    await reset.getByRole('button', { name: 'Back to sign in' }).click();
    await reset.getByLabel('Workspace address').fill(`smoke-theater-${stamp}`);
    await reset.getByLabel('Email address').fill(`owner-${stamp}@smoke.test`);
    await reset.getByLabel('Password', { exact: true }).fill('NewP@ssw0rd123');
    await reset.getByRole('button', { name: 'Sign in' }).click();
    await reset.waitForURL(`${base}/theater`);
    console.log('✓ tenant password reset and re-login');

    const groupContext = await browser.newContext();
    const group = await newPage(groupContext);
    await group.goto(`${base}/register/group`, { waitUntil: 'networkidle' });
    await group.getByLabel('Group name').fill(`Smoke Group ${stamp}`);
    await group.getByLabel('Workspace address').fill(`smoke-group-${stamp}`);
    await group.getByLabel('Contact person').fill('Smoke Contact');
    await group.getByLabel('Email address').fill(`group-${stamp}@smoke.test`);
    await group.getByLabel('Phone number').fill('500000002');
    await group.getByLabel('About the group').fill('A smoke-test theater group.');
    await group.locator('input[type="file"]').setInputFiles({
        name: 'permit.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4 smoke permit'),
    });
    await group.getByLabel('Password', { exact: true }).fill('P@ssw0rd123');
    await group.getByLabel('Confirm password').fill('P@ssw0rd123');
    await group.getByRole('button', { name: 'Create workspace' }).click();
    await verifyOtp(group);
    await group.waitForURL(`${base}/group/application`);
    await group.getByText('Group application').waitFor();
    await group.getByText('Your application is waiting for review. We will notify you when there is an update.').waitFor();
    await group.goto(`${base}/group`, { waitUntil: 'networkidle' });
    await group.waitForURL(`${base}/group/application`);
    console.log('✓ group registration, pending application, dashboard guard');

    await theater.goto(`${base}/admin/applications`, { waitUntil: 'networkidle' });
    await theater.getByText('Group applications').first().waitFor();
    await theater.getByPlaceholder(/Search by group/).fill(`Smoke Group ${stamp}`);
    await theater.getByText(`Smoke Group ${stamp}`).first().waitFor();
    await theater.getByRole('link', { name: 'Review application' }).first().click();
    await theater.getByText('Group information').waitFor();

    await theater.getByRole('button', { name: 'Reject', exact: true }).first().click();
    await theater.getByLabel('Rejection reason').fill('Please upload a clearer permit.');
    await theater.getByRole('button', { name: 'Confirm' }).click();
    await theater.getByText('Document rejected.').waitFor();

    await group.goto(`${base}/group/application`, { waitUntil: 'networkidle' });
    await group.getByRole('button', { name: 'Upload replacement' }).click();
    await group.locator('input[type="file"]').setInputFiles({
        name: 'replacement.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('%PDF-1.4 replacement permit'),
    });
    await group.getByRole('button', { name: 'Upload replacement' }).last().click();
    await group.getByText('Replacement uploaded. Your application is back in review.').waitFor();

    await theater.reload({ waitUntil: 'networkidle' });
    await theater.getByRole('button', { name: 'Accept', exact: true }).click();
    await theater.getByText('Document accepted.').waitFor();
    await theater.getByRole('button', { name: 'Approve application' }).click();
    await theater.getByRole('button', { name: 'Confirm' }).last().click();
    await theater.getByText('Application approved.').waitFor();

    await group.goto(`${base}/group`, { waitUntil: 'networkidle' });
    await group.getByText('Group dashboard').waitFor();
    await group.getByRole('button', { name: 'Notifications' }).click();
    await group.getByText('Application approved').first().waitFor();
    await group.getByRole('button', { name: 'Close notifications' }).click();
    console.log('✓ admin document review, group resubmission, approval, and notification');

    await group.goto(`${base}/group/theaters`, { waitUntil: 'networkidle' });
    await group.getByText(`Smoke Theater ${stamp}`).first().click();
    await group.getByRole('heading', { name: 'Available slots', exact: true }).waitFor();
    await group.getByRole('button', { name: 'Request slot' }).first().waitFor();
    await group.getByRole('button', { name: 'Request slot' }).first().click();
    await group.getByRole('button', { name: 'Request slot' }).last().click();
    await group.getByText('Reservation request submitted.').waitFor();
    await group.waitForURL(/\/group\/reservations\/\d+/);
    const reservationUrl = group.url();
    await group.getByText('Waiting for the theater decision.').waitFor();
    console.log('✓ group discovers theater, hall, slot, and requests a reservation');

    await theater.goto(`${base}/theater/login`, { waitUntil: 'networkidle' });
    await theater.getByLabel('Workspace address').fill(`smoke-theater-${stamp}`);
    await theater.getByLabel('Email address').fill(`owner-${stamp}@smoke.test`);
    await theater.getByLabel('Password', { exact: true }).fill('NewP@ssw0rd123');
    await theater.getByRole('button', { name: 'Sign in' }).click();
    await theater.waitForURL(`${base}/theater`);
    await theater.goto(`${base}/theater/reservations`, { waitUntil: 'networkidle' });
    await theater.getByText('Reservations', { exact: true }).first().waitFor();
    await theater.getByRole('link', { name: 'View' }).first().click();
    await theater.getByRole('button', { name: 'Approve reservation' }).click();
    await theater.getByRole('button', { name: 'Confirm' }).last().click();
    await theater.getByText('Reservation approved.').waitFor();
    await theater.getByText('This reservation is approved. The slot is reserved.').waitFor();
    console.log('✓ theater approves reservation and reserves the slot');

    await group.goto(reservationUrl, { waitUntil: 'networkidle' });
    await group.getByText('This reservation is approved. The slot is reserved.').waitFor();
    await group.getByText('Approved', { exact: true }).first().waitFor();
    console.log('✓ group sees the approved reservation');

    const userContext = await browser.newContext();
    const user = await newPage(userContext);
    await user.goto(`${base}/register/user`, { waitUntil: 'networkidle' });
    await user.getByLabel('Name').fill('Smoke Audience');
    await user.getByLabel('Email address').fill(`user-${stamp}@smoke.test`);
    await user.getByLabel('Phone number').fill(`5${stamp}${Math.floor(Math.random() * 10)}`);
    await user.getByLabel('Password', { exact: true }).fill('P@ssw0rd123');
    await user.getByLabel('Confirm password').fill('P@ssw0rd123');
    await user.getByRole('button', { name: 'Create workspace' }).click();
    await verifyOtp(user);
    await user.waitForURL(`${base}/user`);
    await user.getByText('Your TheaterLink').waitFor();
    console.log('✓ individual registration and dashboard');

    await user.goto(`${base}${publicHref}`, { waitUntil: 'networkidle' });
    await user.getByText(`Smoke Event ${stamp}`).waitFor();
    await user.getByRole('button', { name: 'Increase quantity' }).first().click();
    await user.getByRole('button', { name: 'Confirm booking' }).click();
    await user.waitForURL(/\/user\/bookings\/\d+/);
    await user.getByText('Your tickets are confirmed.').waitFor();
    await user.getByText('Present this QR code at the entrance.').first().waitFor();
    const bookingUrl = user.url();
    const bookingId = Number(bookingUrl.split('/').pop());
    const userToken = await user.evaluate(() => {
        const raw = window.sessionStorage.getItem('theaterlink.session');
        return raw ? JSON.parse(raw).token : null;
    });
    const qrToken = await user.evaluate(async ({ id, token }) => {
        const response = await fetch(`/api/v1/user/bookings/${id}`, {
            headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        const payload = await response.json();
        return payload.data?.tickets?.[0]?.qr_token ?? null;
    }, { id: bookingId, token: userToken });
    if (!qrToken) throw new Error('Booking QR token was not rendered by the API.');
    console.log('✓ individual discovers event, books tickets, and sees QR');

    await theater.goto(`${base}/theater/tickets/validate`, { waitUntil: 'networkidle' });
    await theater.getByLabel('QR token').fill(qrToken);
    await theater.getByRole('button', { name: 'Validate ticket' }).click();
    await theater.getByText('Ticket valid. Entry recorded.').first().waitFor();
    await user.reload({ waitUntil: 'networkidle' });
    await user.getByText('Used', { exact: true }).first().waitFor();
    console.log('✓ theater validates ticket QR once');

    const arabicContext = await browser.newContext();
    const arabic = await newPage(arabicContext);
    await arabic.goto(base, { waitUntil: 'networkidle' });
    await arabic.getByRole('button', { name: 'AR' }).first().click();
    await arabic.locator('html[dir="rtl"]').waitFor();
    await arabic.getByText('منصة واحدة لكل مسرح وفرقة وجمهور.').waitFor();
    console.log('✓ Arabic RTL switch');

    if (errors.length > 0) {
        throw new Error(`Browser errors:\n${errors.join('\n')}`);
    }

    console.log('\nFrontend smoke test passed.');
} catch (error) {
    console.error('\nFrontend smoke test failed.');
    console.error(error);
    console.error('Browser errors:', errors);
    if (currentPage) {
        await currentPage.screenshot({ path: '/tmp/opencode/frontend-smoke-failure.png', fullPage: true }).catch(() => {});
        console.error('Screenshot: /tmp/opencode/frontend-smoke-failure.png', currentPage.url());
    }
    process.exitCode = 1;
} finally {
    await browser.close();
}
