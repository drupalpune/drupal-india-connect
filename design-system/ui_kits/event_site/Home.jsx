/* global window, React */
const {
  SiteHero25, Countdown, Section, Group, Heading, Text, Button,
  CardIcon, Card, StatCard, Accordion, AccordionContainer, Cta, HeroSideBySide,
} = window.DS;

window.Screens = window.Screens || {};

const WHY = [
  { icon: 'star', text: 'Learn from practitioners', body: 'Real project stories from teams shipping Drupal at scale across the region.' },
  { icon: 'user-plus', text: 'Hire, partner, be found', body: 'Agencies, end users and freelancers in one place, with a job board on site.' },
  { icon: 'rocket', text: 'Contribute together', body: 'Mentored sprints where first-time contributors get their first issue credit.' },
  { icon: 'map-pin', text: 'Grow the region', body: 'Help shape what a shared Asian Drupal community looks like from here on.' },
];

const MILESTONES = [
  ['Jun 2026', 'Call for sessions opens'],
  ['Sep 2026', 'Submissions close'],
  ['Oct 2026', 'Speakers announced, schedule published'],
  ['Jan 2027', 'DrupalAsia Connect, 18–20 January'],
];

const FAQS = [
  ['Where will DrupalAsia Connect be held?', 'The host city is being finalised with regional organisers and will be announced along with the venue, hotel partners and travel guidance. Dates are fixed: 18–20 January 2027.'],
  ['When do tickets go on sale?', 'Early-bird tickets are released to the mailing list first, shortly after the venue announcement.'],
  ['Can I speak if I have never presented before?', 'Yes. A share of the programme is reserved for first-time speakers, each paired with a mentor.'],
  ['Will there be travel or ticket grants?', 'A sponsor-funded grant programme covers tickets and part of the travel cost. Applications open with the CFP.'],
];

window.Screens.Home = function Home() {
  return (
    <>
      <SiteHero25
        eventName="DrupalAsia Connect 2027"
        description={<Text size="lg">Three days of sessions, workshops, contribution sprints and hallway conversations — bringing together the makers, agencies and organisations building Drupal across Asia.</Text>}
        ctaLabel="Watch the teaser"
        ctaHref="#teaser"
        dates="18–20 January 2027"
        location="Asia — city TBA"
        days={[
          { label: 'Day one', info: 'Sessions and keynote' },
          { label: 'Day two', info: 'Sessions and workshops' },
          { label: 'Day three', info: 'Contribution sprints' },
        ]}
        posterSrc="../../assets/textures/trees.webp"
        textureSrc="../../assets/textures/roids.webp"
      />

      <Section backgroundColor="black" darkBackground fullWidth paddingBlockStart="32" paddingBlockEnd="32">
        <Countdown
          target="2027-01-18T09:00:00+05:30"
          eyebrow="Countdown to day one"
          label="Monday 18 January 2027, 09:00"
          variant="dark"
        />
      </Section>

      <Section
        columns="33-67"
        marginBlockStart="96"
        marginBlockEnd="96"
        header={<Heading text="The event" level={2} size="xs" style="accented" />}
      >
        <Heading text="One Drupal community, one continent" level={2} size="6xl" />
        <Group direction="column" gap="lg" selfAlign="start">
          <Text>DrupalAsia Connect brings the regional camps of Asia into a single shared moment. Instead of a dozen conversations happening in parallel, the region gets one stage — for the agencies scaling Drupal delivery, the teams maintaining modules, the government and university platforms running on it, and the people who want to start.</Text>
          <Text>Expect a programme built from the community: talks and panels, hands-on workshops, contribution sprints, a business track for agency and end-user leadership, and plenty of unstructured time for the conversations that only happen in person.</Text>
        </Group>
      </Section>

      <Section
        columns="25-25-25-25"
        mobileColumns="2"
        backgroundColor="accent"
        fullWidth
        paddingBlockStart="64"
        paddingBlockEnd="64"
        header={<Heading text="Why attend" level={2} size="5xl" />}
      >
        {WHY.map((w) => (
          <CardIcon
            key={w.text}
            icon={w.icon}
            iconStyle="primary-background"
            iconAlign="left"
            textAlign="left"
            text={w.text}
            description={w.body}
            backgroundColor="background"
            borderRadius="medium"
          />
        ))}
      </Section>

      <Section
        columns="50-50"
        backgroundColor="black"
        darkBackground
        fullWidth
        paddingBlockStart="64"
        paddingBlockEnd="64"
        header={<Heading text="Call for sessions" level={2} size="xs" style="accented" color="inverted" />}
      >
        <Group direction="column" gap="lg" selfAlign="start">
          <Heading text="The programme is yours to write" level={2} size="6xl" color="inverted" />
          <Text color="inverted">Submissions open once the venue is confirmed. First-time speakers are explicitly welcome — we pair every accepted new speaker with a mentor to help shape the talk.</Text>
          <Group direction="row" gap="md">
            <Button label="Tell me when the CFP opens" variant="primary-inverted" size="large" href="#notify" />
            <Button label="What makes a good session?" variant="secondary-inverted" size="large" href="#faq" />
          </Group>
        </Group>
        <Group direction="column" gap="md" selfAlign="start" equalChildren>
          {MILESTONES.map(([when, what], i) => (
            <div key={when} style={{
              display: 'flex',
              gap: 'var(--sp1-5)',
              padding: 'var(--sp1) var(--sp1-5)',
              background: 'rgba(255,255,255,0.06)',
              borderLeft: `var(--rule-accent) solid ${i === MILESTONES.length - 1 ? 'var(--color--secondary-50)' : 'var(--color--primary-50)'}`,
            }}>
              <span style={{ minWidth: '6.5rem', fontSize: '0.875rem', fontWeight: 700, color: 'var(--text-on-dark-accent)' }}>{when}</span>
              <span style={{ fontSize: '1.0625rem', color: '#fff' }}>{what}</span>
            </div>
          ))}
        </Group>
      </Section>

      <Section
        columns="33-33-33"
        marginBlockStart="96"
        marginBlockEnd="96"
        header={<Heading text="Ticket tiers" level={2} size="5xl" />}
      >
        {[
          ['Early bird', 'Limited allocation, released first to the mailing list.'],
          ['Standard', 'The regular conference pass for individuals and teams.'],
          ['Student & community', 'Concession rate, plus a grant programme for travel support.'],
        ].map(([tier, blurb]) => (
          <Card key={tier} headingText={tier} text={blurb} background="background" />
        ))}
      </Section>

      <Section backgroundColor="accent" fullWidth paddingBlockStart="64" paddingBlockEnd="64">
        <HeroSideBySide media="../../assets/textures/trees.webp" mediaAlt="" imagePosition="right" imageSize="4:3" imageRadius="large">
          <Heading text="Venue & travel" level={2} size="xs" style="accented" />
          <Heading text="Host city announcement soon" level={2} size="5xl" />
          <Text>We are finalising the host city with regional organisers. The shortlist is built around an international airport with direct regional connections, visa access for as much of Asia as possible, and affordable accommodation within walking distance.</Text>
          <Group direction="row" gap="lg">
            <StatCard stat="3" description="Days, one venue" />
            <StatCard stat="4" description="Tracks running in parallel" />
          </Group>
        </HeroSideBySide>
      </Section>

      <Section
        columns="33-67"
        marginBlockStart="96"
        marginBlockEnd="96"
        header={<Heading text="Questions" level={2} size="5xl" />}
      >
        <Text size="sm">Something not covered here? Write to <a href="mailto:hello@drupalasia.org">hello@drupalasia.org</a>.</Text>
        <AccordionContainer>
          {FAQS.map(([q, a], i) => (
            <Accordion key={q} title={q} openByDefault={i === 0}>
              <Text size="sm">{a}</Text>
            </Accordion>
          ))}
        </AccordionContainer>
      </Section>

      <Cta
        headingText="Be first to know"
        text="Venue, CFP and early-bird tickets — announced to the list before anywhere else. No more than one email a month."
        backgroundColor="secondary"
        actions={<Button label="Keep me posted" variant="primary-inverted" size="large" href="#notify" />}
      />
    </>
  );
};
