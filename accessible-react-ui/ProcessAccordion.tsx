"use client";

import { useState } from "react";

type ProcessStep = {
  step: string;
  title: string;
  body: string;
};

const steps: ProcessStep[] = [
  {
    step: "01",
    title: "Discovery call",
    body: "A free 30-minute call to understand your needs, your current situation, and whether we're a good fit. No pitch, no pressure.",
  },
  {
    step: "02",
    title: "Proposal",
    body: "You'll get a written proposal outlining the scope, deliverables, timeline, and price. Everything is agreed in writing before any work starts.",
  },
  {
    step: "03",
    title: "Build",
    body: "Work begins. You'll have a clear point of contact, regular updates, and the ability to give feedback throughout. For retainers and fractional engagements, we establish a rhythm that works for you and your team.",
  },
  {
    step: "04",
    title: "Handoff",
    body: "Delivery includes proper documentation and a walkthrough, so you and your team can run with it.",
  },
];

export default function ProcessAccordion() {
  const [openIndex, setOpenIndex] = useState<number>(0);

  return (
    <div className="flex flex-col gap-4">
      {steps.map((item, i) => {
        const isOpen = openIndex === i;
        const panelId = `process-panel-${item.step}`;
        const buttonId = `process-button-${item.step}`;
        return (
          <div
            key={item.step}
            className={`rounded-3xl border border-diglotus-carbon shadow-[4px_4px_0px_0px_#191A23] transition-colors duration-200 ${
              isOpen ? "bg-diglotus-green" : "bg-white"
            }`}
          >
            <button
              id={buttonId}
              className="w-full flex items-center gap-6 px-8 py-6 text-left"
              onClick={() => setOpenIndex(isOpen ? -1 : i)}
              aria-expanded={isOpen}
              aria-controls={panelId}
            >
              <span className="font-mono text-4xl font-medium text-diglotus-carbon shrink-0 w-14">
                {item.step}
              </span>
              <span className="text-xl font-medium text-diglotus-carbon flex-1">
                {item.title}
              </span>
              <span
                aria-hidden="true"
                className="shrink-0 w-9 h-9 rounded-full border border-diglotus-carbon flex items-center justify-center text-diglotus-carbon text-xl font-light"
              >
                {isOpen ? "−" : "+"}
              </span>
            </button>
            {/* Kept in the DOM rather than unmounted so aria-controls always
                resolves to a real element. While collapsed, `hidden` does take
                the panel out of the accessibility tree — which is intended; the
                association is what matters once it is expanded. */}
            <div
              id={panelId}
              role="region"
              aria-labelledby={buttonId}
              hidden={!isOpen}
            >
              <hr className="border-diglotus-carbon mx-8" />
              <p className="px-8 py-6 text-sm leading-relaxed text-diglotus-carbon">
                {item.body}
              </p>
            </div>
          </div>
        );
      })}
    </div>
  );
}
