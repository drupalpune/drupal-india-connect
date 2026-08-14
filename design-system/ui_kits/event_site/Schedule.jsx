/* global window, React */
const { Section, Heading, Text, Badge, Card } = window.DS;

window.Screens = window.Screens || {};

const SLOTS = [
  { time: '09:00', items: [{ title: 'Registration and coffee', room: 'Foyer', track: 'Community' }] },
  { time: '10:00', items: [{ title: 'Opening keynote', room: 'Hall A', track: 'Keynote' }] },
  {
    time: '11:30',
    items: [
      { title: 'Scaling Drupal delivery across borders', room: 'Hall A', track: 'Business' },
      { title: 'Config management in anger', room: 'Room 2', track: 'Development' },
    ],
  },
  { time: '14:00', items: [{ title: 'Contribution sprint — mentored', room: 'Sprint room', track: 'Community' }] },
];

window.Screens.Schedule = function Schedule() {
  return (
    <>
      <Section marginBlockStart="64" marginBlockEnd="48" header={<Heading text="Schedule" level={2} size="xs" style="accented" />}>
        <Heading text="Schedule published October 2026" level={1} size="7xl" />
        <Text size="lg">Placeholder programme showing the schedule layout: sticky time slots down the left, sessions to the right, divided by the primary rule.</Text>
      </Section>

      <Section marginBlockEnd="96">
        <div className="view-session-schedule" style={{ width: '100%' }}>
          <div className="view-content">
            {SLOTS.map((slot, i) => (
              <React.Fragment key={slot.time}>
                {i > 0 && <hr className="view-session-schedule__divider" />}
                <h2 className="view-session-schedule__title">{slot.time}</h2>
                {slot.items.map((item) => (
                  <div className="view-details-list__item view-session-schedule__item" key={item.title}>
                    <Card
                      headingText={item.title}
                      text={`${item.room} · ${item.track}`}
                      url="#schedule"
                      background="background"
                      level={3}
                    />
                  </div>
                ))}
              </React.Fragment>
            ))}
          </div>
        </div>
      </Section>

      <Section columns="50-50" marginBlockEnd="96" header={<Heading text="Legend" level={2} size="4xl" />}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--sp0-75)' }}>
          <Badge label="Keynote" icon="star" />
          <Badge label="Development" style="secondary" />
          <Badge label="Business" style="secondary" />
          <Badge label="Community" style="secondary" />
        </div>
        <Text size="sm">Sessions link to their own page, where speakers, room and evaluation live.</Text>
      </Section>
    </>
  );
};
