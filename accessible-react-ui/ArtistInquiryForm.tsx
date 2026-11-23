"use client";

import { useEffect, useRef, useState } from "react";
import Button from "@/app/components/Button";
import FieldError from "@/app/components/FieldError";
import Honeypot from "@/app/components/Honeypot";
import { useFieldErrors } from "@/app/lib/use-field-errors";
import {
  ARTIST_NEEDS,
  HONEYPOT,
  MAX_LENGTH,
  ARTIST_RULES,
} from "@/app/lib/contact-fields";

type FormState = "idle" | "submitting" | "success" | "error";

export default function ArtistInquiryForm() {
  const [formState, setFormState] = useState<FormState>("idle");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: "",
    email: "",
    practice: "",
    workLink: "",
    need: "",
    message: "",
    [HONEYPOT]: "",
  });
  const successRef = useRef<HTMLDivElement>(null);
  const formRef = useRef<HTMLFormElement>(null);
  const { errors, onChange, validateAll, reset, fieldProps } =
    useFieldErrors(ARTIST_RULES);

  // The form is replaced on success, so move focus to the confirmation;
  // otherwise focus is orphaned on a button that no longer exists.
  useEffect(() => {
    if (formState === "success") successRef.current?.focus();
  }, [formState]);

  function handleChange(
    e: React.ChangeEvent<
      HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
    >,
  ) {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    onChange(e);
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    // Every problem is shown at once; focus goes to the first so a keyboard
    // or screen reader user lands on it and hears its message.
    const invalid = validateAll(form);
    if (invalid) {
      const field = formRef.current?.elements.namedItem(invalid);
      if (field instanceof HTMLElement) field.focus();
      return;
    }

    setFormState("submitting");
    setErrorMessage(null);

    try {
      const res = await fetch("/api/contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...form, kind: "artist" }),
      });

      if (!res.ok) {
        // 400 and 429 carry a message written for the visitor: what to fix,
        // or when to try again. Anything else gets the generic fallback.
        if (res.status === 400 || res.status === 429) {
          const { error } = await res.json().catch(() => ({}));
          if (typeof error === "string") setErrorMessage(error);
        }
        throw new Error("Failed");
      }
      setFormState("success");
      reset();
      setForm({
        name: "",
        email: "",
        practice: "",
        workLink: "",
        need: "",
        message: "",
        [HONEYPOT]: "",
      });
    } catch {
      setFormState("error");
    }
  }

  const inputClass =
    "w-full bg-diglotus-gravel border border-diglotus-carbon aria-invalid:border-red-700 rounded-xl px-4 py-3 font-sans text-diglotus-carbon focus:outline-none focus:ring-2 focus:ring-diglotus-green transition";

  const labelClass = "block font-mono font-bold text-sm mb-2";

  if (formState === "success") {
    return (
      <div
        ref={successRef}
        tabIndex={-1}
        role="status"
        className="bg-diglotus-green rounded-3xl p-10 flex flex-col gap-4 border border-diglotus-carbon"
      >
        <h3 className="text-2xl font-bold">Inquiry sent!</h3>
        <p>
          Thanks for reaching out. If this quarter&apos;s slot is already spoken
          for, your inquiry stays on the list to be considered in a subsequent
          quarter.
        </p>
      </div>
    );
  }

  return (
    <form
      ref={formRef}
      noValidate
      onSubmit={handleSubmit}
      className="bg-diglotus-gravel rounded-3xl p-8 flex flex-col gap-6 border border-diglotus-carbon shadow-lg"
    >
      <div className="grid md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="name" className={labelClass}>
            Name
          </label>
          <input
            id="name"
            name="name"
            {...fieldProps("name")}
            type="text"
            autoComplete="name"
            required
            maxLength={MAX_LENGTH.name}
            value={form.name}
            onChange={handleChange}
            placeholder="Your name"
            className={inputClass}
          />
          <FieldError name="name" message={errors.name} />
        </div>
        <div>
          <label htmlFor="email" className={labelClass}>
            Email
          </label>
          <input
            id="email"
            name="email"
            {...fieldProps("email")}
            type="email"
            autoComplete="email"
            required
            maxLength={MAX_LENGTH.email}
            value={form.email}
            onChange={handleChange}
            placeholder="you@example.com"
            className={inputClass}
          />
          <FieldError name="email" message={errors.email} />
        </div>
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="practice" className={labelClass}>
            What&apos;s your practice?
          </label>
          <input
            id="practice"
            name="practice"
            {...fieldProps("practice")}
            type="text"
            required
            maxLength={MAX_LENGTH.practice}
            value={form.practice}
            onChange={handleChange}
            placeholder="Painter, ceramicist, DJ, poet…"
            className={inputClass}
          />
          <FieldError name="practice" message={errors.practice} />
        </div>
        <div>
          <label htmlFor="workLink" className={labelClass}>
            Link to your work{" "}
            <span className="font-normal opacity-70">(optional)</span>
          </label>
          <input
            id="workLink"
            name="workLink"
            {...fieldProps("workLink")}
            type="url"
            maxLength={MAX_LENGTH.workLink}
            value={form.workLink}
            onChange={handleChange}
            placeholder="Instagram, current site, anywhere"
            className={inputClass}
          />
          <FieldError name="workLink" message={errors.workLink} />
        </div>
      </div>

      <div>
        <label htmlFor="need" className={labelClass}>
          What do you need?
        </label>
        {/* The native caret sits flush against the control's right border,
            which reads as detached on a field this wide. appearance-none
            drops it, and the drawn chevron sits at the same inset as the
            field's own text padding. */}
        <div className="relative">
          <select
            id="need"
            name="need"
            {...fieldProps("need")}
            required
            value={form.need}
            onChange={handleChange}
            className={`${inputClass} appearance-none pr-10`}
          >
            <option value="" disabled>
              Select one
            </option>
            {ARTIST_NEEDS.map((n) => (
              <option key={n} value={n}>
                {n}
              </option>
            ))}
          </select>
          <svg
            aria-hidden="true"
            className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4"
            viewBox="0 0 16 16"
            fill="none"
          >
            <path
              d="M4 6l4 4 4-4"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </div>
        <FieldError name="need" message={errors.need} />
      </div>

      <div>
        <label htmlFor="message" className={labelClass}>
          Tell me about it
        </label>
        <textarea
          id="message"
          name="message"
          {...fieldProps("message")}
          required
          maxLength={MAX_LENGTH.message}
          rows={5}
          value={form.message}
          onChange={handleChange}
          placeholder="What you make, what the site should do for it, and anything else worth knowing."
          className={inputClass}
        />
        <FieldError name="message" message={errors.message} />
      </div>

      <Honeypot value={form[HONEYPOT]} onChange={handleChange} />

      {formState === "error" && (
        <p role="alert" className="text-red-700 font-mono text-sm">
          {errorMessage ?? (
            <>
              Something went wrong. Try emailing directly at{" "}
              <a
                href="mailto:contact@digitallotusdev.com"
                className="underline"
              >
                contact@digitallotusdev.com
              </a>
              .
            </>
          )}
        </p>
      )}

      <Button
        type="submit"
        disabled={formState === "submitting"}
        variant="secondary"
      >
        {formState === "submitting" ? "Sending..." : "Send inquiry"}
      </Button>
    </form>
  );
}
