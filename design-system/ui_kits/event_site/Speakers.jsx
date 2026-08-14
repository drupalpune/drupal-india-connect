/* global window, React */
const { Section, Heading, Text, FeaturedSpeaker, Badge } = window.DS;

window.Screens = window.Screens || {};

const SPEAKERS = [
  { name: 'Speaker one', badge: 'Keynote', body: 'Talk title to be announced' },
  { name: 'Speaker two', badge: 'Session', body: 'Talk title to be announced' },
  { name: 'Speaker three', badge: 'Workshop', body: 'Talk title to be announced' },
];

window.Screens.Speakers = function Speakers() {
  return (
    <>
      <Section marginBlockStart="64" marginBlockEnd="48" header={<Heading text="Speakers" level={2} size="xs" style="accented" />}>
        <Heading text="Speakers announced October 2026" level={1} size="7xl" />
        <Text size="lg">The programme is selected from the open call for sessions. Until then, here is how the featured-speaker row will look — portraits get the duotone treatment, colourising on hover.</Text>
      </Section>

      <div className="block-views-blockfeatured-speakers-block-1">
        <div className="view-featured-speakers__content container">
          <h2 className="view-featured-speakers__title">Featured speakers</h2>
          {SPEAKERS.map((s) => (
            <div className="view-featured-speakers__item" key={s.name}>
              <FeaturedSpeaker
                name={s.name}
                badge={s.badge}
                body={s.body}
                href="#speakers"
                image="../../assets/textures/roids.webp"
                imageAlt=""
              />
            </div>
          ))}
        </div>
      </div>

      <Section columns="50-50" marginBlockStart="96" marginBlockEnd="96" header={<Heading text="Session tracks" level={2} size="5xl" />}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--sp0-75)' }}>
          <Badge label="Development" icon="check" />
          <Badge label="DevOps" style="secondary" />
          <Badge label="Content & editorial" style="secondary" />
          <Badge label="Business & agency" style="secondary" />
          <Badge label="Community" style="secondary" />
        </div>
        <Text>Tracks are confirmed once submissions close. Every accepted first-time speaker is paired with a mentor for rehearsal and feedback.</Text>
      </Section>
    </>
  );
};
