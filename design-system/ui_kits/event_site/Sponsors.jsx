/* global window, React */
const { Section, Heading, Text, Button, SponsorTeaser, Cta, StatCard } = window.DS;

window.Screens = window.Screens || {};

const TIERS = [
  { tier: 'Founding', logos: ['../../assets/icons/drupal-evergreen.svg', '../../assets/icons/drupal-rocket.svg'] },
  { tier: 'Supporting', logos: ['../../assets/icons/palm-tree.svg', '../../assets/icons/drupal-evergreen.svg', '../../assets/icons/drupal-rocket.svg'] },
];

window.Screens.Sponsors = function Sponsors() {
  return (
    <>
      <Section marginBlockStart="64" marginBlockEnd="48" header={<Heading text="Sponsors" level={2} size="xs" style="accented" />}>
        <Heading text="Sponsor DrupalAsia Connect" level={1} size="7xl" />
        <Text size="lg">Sponsorship keeps tickets affordable and funds travel grants for community members across the region. The prospectus, with tiers and booth options, is available on request.</Text>
      </Section>

      {TIERS.map((t) => (
        <Section key={t.tier} marginBlockEnd="64" header={<Heading text={t.tier + ' sponsors'} level={2} size="xs" style="accented" />}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--grid-gap)', width: '100%' }}>
            {t.logos.map((logo, i) => (
              <SponsorTeaser key={t.tier + i} name="Sponsor name" logo={logo} href="#sponsors" />
            ))}
          </div>
        </Section>
      ))}

      <Section columns="33-33-33" backgroundColor="black" darkBackground fullWidth paddingBlockStart="64" paddingBlockEnd="64"
        header={<Heading text="What sponsorship funds" level={2} size="5xl" color="inverted" />}>
        <StatCard stat="100%" description="Of grant funding comes from sponsors" />
        <StatCard stat="3" description="Days of programme and sprints" />
        <StatCard stat="1" description="Shared stage for the whole region" />
      </Section>

      <Cta
        headingText="Request the prospectus"
        text="Tiers, booth options and audience detail, sent the same day."
        backgroundColor="inverted"
        actions={<Button label="Email the sponsorship team" variant="primary-inverted" size="large" href="mailto:sponsors@drupalasia.org" />}
      />
    </>
  );
};
