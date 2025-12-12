<?php
// File: modules/giveaways/functions/rafflepress-rules-generation.php

function admin_lab_generate_rafflepress_rules(array $args): string {
    $age             = isset($args['minimum_age']) ? (int) $args['minimum_age'] : 18;
    $countries       = !empty($args['eligible_countries']) ? implode(', ', array_map('trim', $args['eligible_countries'])) : 'France';
    $start_date      = $args['start_date'] ?? '2025-01-01';
    $end_date        = $args['end_date'] ?? '2025-12-31';
    $sponsor_name    = $args['sponsor_name'] ?? 'Le Sponsor';
    $sponsor_email   = $args['sponsor_email'] ?? 'contact@sponsor.com';
    $sponsor_country = $args['sponsor_country'] ?? 'France';

    $lines = [
        __('NO PURCHASE NECESSARY TO ENTER OR WIN. MAKING A PURCHASE OR PAYMENT OF ANY KIND WILL NOT INCREASE YOUR CHANCES OF WINNING. VOID WHERE PROHIBITED OR RESTRICTED BY LAW.', 'me5rine-lab'),
        '',
        sprintf(__('PROMOTION DESCRIPTION: The contest ("Contest") begins on %s and ends on %s.', 'me5rine-lab'), $start_date, $end_date),
        '',
        sprintf(__('The sponsor of this Contest is %s (the "Sponsor"). By participating in the Contest, each entrant unconditionally accepts and agrees to comply with and abide by these Official Rules and the decisions of the Sponsor, which shall be final and binding in all respects. The Sponsor is responsible for the collection, submission and processing of entries as well as the overall administration of the Contest. For any questions, comments, or problems related to the Contest, participants should contact the Sponsor exclusively at: %s during the Promotion Period.', 'me5rine-lab'), $sponsor_name, $sponsor_email),
        '',
        sprintf(__('ELIGIBILITY: Open to legal residents of the following countries: %s, aged %d years or older (the "Entrant").', 'me5rine-lab'), $countries, $age),
        __('The Sponsor and its parent companies, subsidiaries, affiliates, distributors, retailers, commercial representatives, advertising and promotion agencies and each of their officers, directors and employees (the "Promotion Entities") are not eligible to enter or win a prize. Household Members and Immediate Family Members of such individuals are also not eligible. "Household Members" means people who share the same residence at least three months per year. "Immediate Family Members" means parents, step-parents, legal guardians, children, step-children, siblings, step-siblings or spouses. This contest is subject to all applicable laws and regulations and is void where prohibited.', 'me5rine-lab'),
        '',
        __('HOW TO ENTER: Participate in the Contest during the promotion period by visiting the online registration form. Automated or robotic entries submitted by individuals or organizations will be disqualified. Entry must be made by the Entrant. Any attempt by an Entrant to obtain more entries than allowed by using multiple email addresses, identities, registrations, logins or other methods (including use of commercial contest entry services) will void that Entrant\'s entries and may result in disqualification. Final eligibility for any prize is subject to verification. All entries must be submitted before the end of the promotion period. The Sponsor\'s database clock will be the official timekeeper for this Contest.', 'me5rine-lab'),
        '',
        __('WINNER SELECTION: The winner(s) will be selected in a random drawing from among all eligible entries received during the promotion period. The drawing will be held within 7 days after the contest ends, conducted by the Sponsor or its representatives. Decisions are final. Odds of winning depend on the total number of eligible entries received.', 'me5rine-lab'),
        '',
        __('WINNER NOTIFICATION: The winner will be contacted by email at the address provided at registration, approximately 7 days after the drawing. The potential winner must accept the prize by email within 7 days. The Sponsor is not responsible for missed or undelivered notifications due to inactive email accounts or other issues.', 'me5rine-lab'),
        __('Any unclaimed or undeliverable prize may result in forfeiture. The winner may be required to sign and return an affidavit of eligibility, release of liability, and a publicity release. No substitution or transfer of prize is permitted except at Sponsor’s discretion.', 'me5rine-lab'),
        '',
        __('PRIVACY: Any personal information provided will be subject to the Sponsor\'s privacy policy, available on its website. By participating in the Contest, you authorize the Sponsor to share your email address and other identifiable information.', 'me5rine-lab'),
        '',
        __('LIMITATION OF LIABILITY: By participating, you agree to release and hold harmless the Sponsor, its affiliates, advertising and promotion agencies, partners, representatives, agents, successors, assigns, employees, officers and directors from any liability, illness, injury, death, loss, litigation, claim, or damage that may occur, directly or indirectly, from participation or acceptance/use/misuse of a prize.', 'me5rine-lab'),
        '',
        sprintf(__('DISPUTES: This contest is governed by the laws in force in %s. All disputes will be handled in the courts of this country. No class actions allowed. You waive any rights to punitive, incidental or consequential damages, and attorney fees.', 'me5rine-lab'), $sponsor_country),
        '',
        sprintf(__('WINNERS LIST: To obtain the list of winners, contact %s within 30 days after the end of the contest.', 'me5rine-lab'), $sponsor_email),
        '',
        sprintf(__('SPONSOR: %s (contact: %s)', 'me5rine-lab'), $sponsor_name, $sponsor_email),
    ];

    return implode("\n", $lines);
}
