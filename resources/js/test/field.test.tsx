import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Field, Input } from '@/components/ui';

describe('form field accessibility', () => {
    it('associates a label and an error with its input', () => {
        render(
            <Field label="Email address" error="Enter a valid email address.">
                <Input type="email" />
            </Field>,
        );

        const input = screen.getByLabelText('Email address');
        expect(input).toBeInTheDocument();
        expect(input).toHaveAttribute('aria-describedby');
        expect(screen.getByText('Enter a valid email address.')).toBeInTheDocument();
    });
});
